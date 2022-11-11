<?php
namespace App\Validator;

use Symfony\Component\Validator\Validator\ValidatorInterface;

class EntityValidator{

    
    private ValidatorInterface $validator;
    private $options;

    public function __construct(ValidatorInterface $validator)
    {
            $this->validator = $validator;
    }

    public function validate($class){
        
         $errors = $this->validator->validate($class);
         $violationList = [];

         if($errors->count() > 0){
            $violationList = [];

            for ($i = 0; $i < $errors->count(); $i++) {
                $violation = $errors->get($i);
                $splitNamespace = explode('\\',get_class($violation->getConstraint()));
                $key = $splitNamespace[array_key_last($splitNamespace)];

                $violationList[] = [
                    "message" => $violation->getMessage(),
                    "type" => $key,
                    "variables" => [
                        "field" => $violation->getPropertyPath()
                    ]
                ];
            }
        }
        return $violationList;
    }

}