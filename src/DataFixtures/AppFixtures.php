<?php

namespace App\DataFixtures;

use App\Entity\Comment;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();

        $parents = [];

        for ($i = 0; $i < 30; $i++) {
            $comment = new Comment();
            $comment->setName($faker->name());
            $comment->setEmail($faker->email());
            $comment->setComment($faker->paragraph());

            if ($faker->boolean(15)) {
                $comment->setDeletedAt(new \DateTimeImmutable());
            }

            $manager->persist($comment);
            $parents[] = $comment;
        }

        $manager->flush();

        foreach ($parents as $parent) {
            $replyCount = $faker->numberBetween(0, 5);

            for ($j = 0; $j < $replyCount; $j++) {
                $reply = new Comment();
                $reply->setName($faker->name());
                $reply->setEmail($faker->email());
                $reply->setComment($faker->sentence());
                $reply->setParent($parent);

                if ($faker->boolean(10)) {
                    $reply->setDeletedAt(new \DateTimeImmutable());
                }

                $manager->persist($reply);
            }
        }

        $manager->flush();
    }
}
