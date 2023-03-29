<?php

namespace App\DataFixtures;

use App\Entity\Category;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;

class CategoriesFixtures extends Fixture
{
    private $root_directory;
    public function __construct(string $root_directory)
    {
        $this->root_directory = $root_directory;
    }

    public function load(ObjectManager $manager): void
    {
        $categoryDatas = json_decode(file_get_contents($this->root_directory . '/src/DataFixtures/jsons/category.json'));

        foreach ($categoryDatas as $categoryDatas) {

            $category = new Category();
            $category->setName($categoryDatas->name);
            $category->setDescription($categoryDatas->description);
            
            $manager->persist($category);            
        }
        $manager->flush();
    }

}