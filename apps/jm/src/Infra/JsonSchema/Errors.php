<?php
namespace App\Infra\JsonSchema;

class Errors
{
    /**
     * @param array $errors
     * @param string $preface
     *
     * @return string
     */
    public static function toString(array $errors, $preface = 'Object validation has failed.')
    {
        $messages = [$preface];
        foreach ($errors as $error) {
            $messages[] = sprintf('Property %s is invalid "%s".', $error['property'], $error['message']);
        }

        return implode(' ', $messages);
    }
}
