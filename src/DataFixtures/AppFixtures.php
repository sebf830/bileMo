<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    private $root_directory;

    public function __construct(string $root_directory)
    {
        $this->root_directory = $root_directory;
    }

    public function load(ObjectManager $manager): void
    {
        $entities = ["category", 'client', 'product', 'user'];

        foreach($entities as $entity){

            $datas = json_decode(file_get_contents($this->root_directory . '/src/DataFixtures/jsons/'.$entity. '.json'), true);
            $namespace = "App\\Entity\\" . ucfirst($entity);

            foreach($datas as $data){
                
                $model = new $namespace;

                foreach($data as $key => $property){
                    if(!is_int($property)){
                        $model->{'set' . ucfirst($key)}($property);
                    }else{
                        $relationNamespace = "App\\Entity\\" . ucfirst($key);
                        $relation = $manager->getRepository($relationNamespace)->find($property);
                        $model->{'set' . ucfirst($key)}($relation);
                    }
                }
                $manager->persist($model);
                $manager->flush();
            }
        }

    }
}
