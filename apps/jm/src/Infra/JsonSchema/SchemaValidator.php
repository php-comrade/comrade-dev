<?php
namespace App\Infra\JsonSchema;

use JsonSchema\Constraints\Constraint;
use JsonSchema\Constraints\Factory;
use JsonSchema\Validator;

class SchemaValidator
{
    /**
     * @var Validator
     */
    private $validator;

    /**
     * @param Factory $factor
     */
    public function __construct(Factory $factor)
    {
        $this->validator = new Validator($factor);
    }

    /**
     * @param  $data
     * @param $schema
     *
     * @return array
     */
    public function validate($data, $schema)
    {
        $this->validator->reset();

        try {
            if (is_string($schema)) {
                $schema = (object) ['$ref' => $schema];
            }

            if (is_array($schema)) {
                $schema = json_decode(json_encode($schema));
            }

            $this->validator->validate($data, $schema, Constraint::CHECK_MODE_TYPE_CAST);

            return $this->validator->isValid() ? [] : $this->validator->getErrors();
        } finally {
            $this->validator->reset();
        }
    }
}
