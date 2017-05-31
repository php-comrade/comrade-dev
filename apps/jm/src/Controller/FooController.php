<?php
namespace App\Controller;

use Formapro\Pvm\CallbackBehavior;
use Formapro\Pvm\Exception\WaitExecutionException;
use Formapro\Pvm\Process;
use Formapro\Pvm\Token;
use Formapro\Pvm\UUID;
use Formapro\Pvm\Visual\GraphVizVisual;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Tests\Logger;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class FooController extends Controller
{
    public function fooAction(Request $request)
    {
        $this->setupBehaviors();

        if ($request->query->get('clear-session', false)) {
            $request->getSession()->set('paypal.processId', null);
            $request->getSession()->set('paypal.tokenId', null);

            return $this->redirectToRoute('paypal_express_checkout');
        }

        if ($processId = $request->getSession()->get('paypal.processId')) {
            $process = $this->getProcessStorage()->findExecution($processId);
        } else {
            $process = $this->createProcess();
        }

        if ($tokenId = $request->getSession()->get('paypal.tokenId')) {
            $token = $process->getToken($tokenId);
        } else {
            foreach ($process->getTransitions() as $transition) {
                if ($transition->getFrom() === null) {
                    break;
                }
            }

            $token = $process->createToken($transition);
        }

        $token->request = $request;

        $waitTokens = $this->getProcessEngine()->proceed($token, $logger = new Logger());

        if ($waitTokens) {
            $request->getSession()->set('paypal.processId', $process->getId());
            $request->getSession()->set('paypal.tokenId', $waitTokens[0]->getId());
        } else {
            $request->getSession()->remove('paypal.processId');
            $request->getSession()->remove('paypal.tokenId');
        }

        $graph = (new GraphVizVisual())->createImageSrc($process);

        $replyRedirect = null;
        $replyContent = null;
        if (isset($waitTokens[0]->reply)) {
            $reply = $waitTokens[0]->reply;
            if ($reply instanceof HttpRedirect) {
                $replyRedirect = $reply->getUrl();
            } else if ($reply instanceof HttpResponse) {
                $replyContent = $reply->getContent();
            } else {
                throw new \LogicException('Unsupported reply', null, $reply);
            }
        }

        $form = null;
        if (isset($waitTokens[0]->form)) {
            $form = $waitTokens[0]->form->createView();
        }

        return $this->render('default/paypal.html.twig', [
            'process' => $process,
            'graph' => $graph,
            'form' => $form,
            'logger' => $logger,
            'reply_content' => $replyContent,
            'reply_redirect' => $replyRedirect,
        ]);
    }

    private function setupBehaviors()
    {
        $repository = $this->getBehaviorRepository();
        $repository->register('PreparePayment', new CallbackBehavior(function(Token $token) {
            /** @var Form $form */
            $form = $this->getFormFactory()->createNamedBuilder('form')
                ->add('amount', NumberType::class, ['constraints' => [new Range(['min' => 1, 'max' => 10])]])
                ->add('order_confirmation_required', CheckboxType::class,['required' => false])
                ->getForm()
            ;

            $token->form = $form;

            if ($token->getTransition()->isWaiting()) {
                $form->handleRequest($token->request);
                if ($form->isValid()) {
                    $token->getProcess()->setValue('payment', [
                        'PAYMENTREQUEST_0_AMT' => $form->getData()['amount'],
                        'PAYMENTREQUEST_0_PAYMENTACTION' => Api::PAYMENTACTION_SALE,
                        'AUTHORIZE_TOKEN_USERACTION' => $form->getData()['order_confirmation_required'] ? null : Api::USERACTION_COMMIT,
                        'RETURNURL' => $this->generateUrl('paypal_express_checkout', [], UrlGeneratorInterface::ABSOLUTE_URL),
                        'CANCELURL' => $this->generateUrl('paypal_express_checkout', ['cancelled' => 1], UrlGeneratorInterface::ABSOLUTE_URL),
                    ]);

                    $token->form = null;

                    return;
                }
            }

            throw new WaitExecutionException();
        }));

        $repository->register('SetExpressCheckout', new CallbackBehavior(function(Token $token) {
            $payment = ArrayObject::ensureArrayObject($token->getProcess()->getValue('payment'));
            try {
                $this->getPayum()->getGateway('paypal')->execute(new SetExpressCheckout($payment));

                if ($payment['L_ERRORCODE0']) {
                    $transition = $token->getProcess()->getOutTransitionWithName($token->getTransition()->getTo(), 'failed');

                    return [$transition];
                }

                $transition = $token->getProcess()->getOutTransitionWithName($token->getTransition()->getTo(), 'get_express_checkout_details');

                return [$transition];
            } finally {
                $token->getProcess()->setValue('payment', $payment);
            }
        }));

        $repository->register('GetExpressCheckout1Details', new CallbackBehavior(function(Token $token) {
            $payment = ArrayObject::ensureArrayObject($token->getProcess()->getValue('payment'));
            try {
                $this->getPayum()->getGateway('paypal')->execute(new GetExpressCheckoutDetails($payment));
            } finally {
                $token->getProcess()->setValue('payment', $payment);
            }
        }));

        $repository->register('GetTransaction1Details', new CallbackBehavior(function(Token $token) {
            $payment = ArrayObject::ensureArrayObject($token->getProcess()->getValue('payment'));
            try {
                foreach (range(0, 9) as $index) {
                    if ($payment['PAYMENTREQUEST_'.$index.'_TRANSACTIONID']) {
                        $this->getPayum()->getGateway('paypal')->execute(new GetTransactionDetails($payment, $index));
                    }
                }
            } finally {
                $token->getProcess()->setValue('payment', $payment);
            }
        }));

        $repository->register('AuthorizeToken', new CallbackBehavior(function(Token $token) {
            $payment = ArrayObject::ensureArrayObject($token->getProcess()->getValue('payment'));
            try {

                $this->getPayum()->getGateway('paypal')->execute(new GetExpressCheckoutDetails($payment));
                foreach (range(0, 9) as $index) {
                    if ($payment['PAYMENTREQUEST_'.$index.'_TRANSACTIONID']) {
                        $this->getPayum()->getGateway('paypal')->execute(new GetTransactionDetails($payment, $index));
                    }
                }

                if ($token->request->query->get('cancelled', false)) {
                    $transition = $token->getProcess()->getOutTransitionWithName($token->getTransition()->getTo(), 'cancelled');

                    return [$transition];
                }

                if ($payment['PAYERID']) {
                    if (Api::USERACTION_COMMIT !== $payment['AUTHORIZE_TOKEN_USERACTION']) {
                        $transition = $token->getProcess()->getOutTransitionWithName($token->getTransition()->getTo(), 'confirm_order');

                        return [$transition];
                    }

                    $transition = $token->getProcess()->getOutTransitionWithName($token->getTransition()->getTo(), 'do_express_checkout_payment');

                    return [$transition];
                }

                $this->getPayum()->getGateway('paypal')->execute(new AuthorizeToken($payment));

                $transition = $token->getProcess()->getOutTransitionWithName($token->getTransition()->getTo(), 'do_express_checkout_payment');

                return [$transition];
            } catch (ReplyInterface $reply) {
                $token->reply = $reply;

                throw new WaitExecutionException();
            } finally {
                $token->getProcess()->setValue('payment', $payment);
            }
        }));

        $repository->register('ConfirmOrder', new CallbackBehavior(function(Token $token) {
            $payment = ArrayObject::ensureArrayObject($token->getProcess()->getValue('payment'));

            try {
                $this->getPayum()->getGateway('paypal')->execute(new ConfirmOrder($payment));
            } catch (ReplyInterface $reply) {
                $token->reply = $reply;

                throw new WaitExecutionException();
            } finally {
                $token->getProcess()->setValue('payment', $payment);
            }
        }));

        $repository->register('DoExpressCheckoutPayment', new CallbackBehavior(function(Token $token) {
            $payment = ArrayObject::ensureArrayObject($token->getProcess()->getValue('payment'));
            try {
                $this->getPayum()->getGateway('paypal')->execute(new DoExpressCheckoutPayment($payment));
            } finally {
                $token->getProcess()->setValue('payment', $payment);
            }
        }));

        $repository->register('Failed', new CallbackBehavior(function(Token $token) {
            $payment = ArrayObject::ensureArrayObject($token->getProcess()->getValue('payment'));

            $this->getPayum()->getGateway('paypal')->execute($status = new GetHumanStatus($payment));

            $token->getProcess()->setValue('status', GetHumanStatus::STATUS_FAILED);
            $token->getProcess()->setValue('payum_status', $status->getValue());
        }));

        $repository->register('Cancelled', new CallbackBehavior(function(Token $token) {
            $payment = ArrayObject::ensureArrayObject($token->getProcess()->getValue('payment'));

            $this->getPayum()->getGateway('paypal')->execute($status = new GetHumanStatus($payment));

            $token->getProcess()->setValue('status', GetHumanStatus::STATUS_CANCELED);
            $token->getProcess()->setValue('payum_status', $status->getValue());
        }));

        $repository->register('Purchased', new CallbackBehavior(function(Token $token) {
            $payment = ArrayObject::ensureArrayObject($token->getProcess()->getValue('payment'));

            $this->getPayum()->getGateway('paypal')->execute($status = new GetHumanStatus($payment));

            $token->getProcess()->setValue('status', GetHumanStatus::STATUS_CAPTURED);
            $token->getProcess()->setValue('payum_status', $status->getValue());
        }));
    }

    /**
     * @return Payum
     */
    private function getPayum()
    {
        return $this->get('payum');
    }

    private function createProcess()
    {
        $process = new Process();
        $process->setId(UUID::generate());

        $preparePaymentTask = $process->createNode();
        $preparePaymentTask->setLabel('PreparePayment');
        $preparePaymentTask->setBehavior('PreparePayment');

        $setExpressCheckoutTask = $process->createNode();
        $setExpressCheckoutTask->setLabel('SetExpressCheckout');
        $setExpressCheckoutTask->setBehavior('SetExpressCheckout');

        $authorizeTokenTask = $process->createNode();
        $authorizeTokenTask->setLabel('AuthorizeToken');
        $authorizeTokenTask->setBehavior('AuthorizeToken');

        $getExpressCheckoutDetails1Task = $process->createNode();
        $getExpressCheckoutDetails1Task->setLabel('GetExpressCheckoutDetails');
        $getExpressCheckoutDetails1Task->setBehavior('GetExpressCheckout1Details');

        $getTransactionDetails1Task = $process->createNode();
        $getTransactionDetails1Task->setLabel('GetTransactionDetails');
        $getTransactionDetails1Task->setBehavior('GetTransaction1Details');

        $getExpressCheckoutDetails2Task = $process->createNode();
        $getExpressCheckoutDetails2Task->setLabel('GetExpressCheckoutDetails');
        $getExpressCheckoutDetails2Task->setBehavior('GetExpressCheckout2Details');

        $getTransactionDetails2Task = $process->createNode();
        $getTransactionDetails2Task->setLabel('GetTransactionDetails');
        $getTransactionDetails2Task->setBehavior('GetTransaction2Details');

        $confirmOrderTask = $process->createNode();
        $confirmOrderTask->setLabel('ConfirmOrder');
        $confirmOrderTask->setBehavior('ConfirmOrder');

        $doExpressCheckoutPaymentTask = $process->createNode();
        $doExpressCheckoutPaymentTask->setLabel('DoExpressCheckoutPayment');
        $doExpressCheckoutPaymentTask->setBehavior('DoExpressCheckoutPayment');

        $failedTask = $process->createNode();
        $failedTask->setLabel('Failed');
        $failedTask->setBehavior('Failed');

        $cancelledTask = $process->createNode();
        $cancelledTask->setLabel('Cancelled');
        $cancelledTask->setBehavior('Cancelled');

        $purchasedTask = $process->createNode();
        $purchasedTask->setLabel('Purchased');
        $purchasedTask->setBehavior('Purchased');

        $process->createTransition(null, $preparePaymentTask);
        $process->createTransition($preparePaymentTask, $setExpressCheckoutTask);

        $process->createTransition($setExpressCheckoutTask, $failedTask, 'failed');
        $process->createTransition($setExpressCheckoutTask, $getExpressCheckoutDetails1Task, 'get_express_checkout_details');

        $process->createTransition($getExpressCheckoutDetails1Task, $getTransactionDetails1Task);

        $process->createTransition($getTransactionDetails1Task, $authorizeTokenTask);

        $process->createTransition($authorizeTokenTask, $cancelledTask, 'cancelled');
        $process->createTransition($authorizeTokenTask, $confirmOrderTask, 'confirm_order');
        $process->createTransition($authorizeTokenTask, $doExpressCheckoutPaymentTask, 'do_express_checkout_payment');

        $process->createTransition($confirmOrderTask, $doExpressCheckoutPaymentTask);

        $process->createTransition($doExpressCheckoutPaymentTask, $purchasedTask);

        return $process;
    }

    /**
     * @return \Formapro\Pvm\MongoProcessStorage
     */
    private function getProcessStorage()
    {
        return $this->get('app.pvm.storage');
    }

    /**
     * @return \Formapro\Pvm\DefaultBehaviorRegistry
     */
    private function getBehaviorRepository()
    {
        return $this->get('app.pvm.behavior_registry');
    }

    /**
     * @return \Formapro\Pvm\ProcessEngine
     */
    private function getProcessEngine()
    {
        return $this->get('app.pvm.process_engine');
    }

    /**
     * @return \Symfony\Component\Form\FormFactory
     */
    private function getFormFactory()
    {
        return $this->get('form.factory');
    }
}
