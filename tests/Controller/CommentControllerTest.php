<?php

namespace App\Tests\Controller;

use App\Entity\Comment;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class CommentControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em = static::getContainer()->get('doctrine')->getManager();
    }

    protected function tearDown(): void
    {
        $this->em->createQuery('DELETE FROM App\Entity\Comment c WHERE c.parent IS NOT NULL')->execute();
        $this->em->createQuery('DELETE FROM App\Entity\Comment c WHERE c.parent IS NULL')->execute();
        $this->em->close();
        parent::tearDown();
    }

    private function makeComment(string $name, ?Comment $parent = null, bool $deleted = false): Comment
    {
        $c = new Comment();
        $c->setName($name);
        $c->setEmail('test@example.com');
        $c->setComment('Body text');
        if ($parent) {
            $c->setParent($parent);
        }
        $this->em->persist($c);
        $this->em->flush();

        if ($deleted) {
            $c->setDeletedAt(new \DateTimeImmutable());
            $this->em->flush();
        }

        return $c;
    }

    public function testFindParentCommentsExcludesDeletedByDefault(): void
    {
        $active = $this->makeComment('Active');
        $this->makeComment('Deleted', null, true);

        $repo = $this->em->getRepository(Comment::class);
        $results = $repo->findParentComments()->getResult();

        $ids = array_map(fn(Comment $c) => $c->getId(), $results);
        $this->assertContains($active->getId(), $ids);

        foreach ($results as $c) {
            $this->assertNull($c->getDeletedAt(), 'Deleted comment should not be returned');
        }
    }

    public function testFindParentCommentsIncludesDeletedWhenFlagSet(): void
    {
        $active  = $this->makeComment('Active');
        $deleted = $this->makeComment('Deleted', null, true);

        $repo    = $this->em->getRepository(Comment::class);
        $results = $repo->findParentComments(true)->getResult();

        $ids = array_map(fn(Comment $c) => $c->getId(), $results);
        $this->assertContains($active->getId(), $ids);
        $this->assertContains($deleted->getId(), $ids);
    }

    public function testFindRepliesExcludesDeletedByDefault(): void
    {
        $parent      = $this->makeComment('Parent');
        $activeReply = $this->makeComment('Reply Active', $parent);
        $this->makeComment('Reply Deleted', $parent, true);

        $repo    = $this->em->getRepository(Comment::class);
        $results = $repo->findReplies($parent)->getResult();

        $ids = array_map(fn(Comment $c) => $c->getId(), $results);
        $this->assertContains($activeReply->getId(), $ids);
        foreach ($results as $c) {
            $this->assertNull($c->getDeletedAt(), 'Deleted reply should not be returned');
        }
    }

    public function testFindRepliesIncludesDeletedWhenFlagSet(): void
    {
        $parent       = $this->makeComment('Parent');
        $activeReply  = $this->makeComment('Reply Active', $parent);
        $deletedReply = $this->makeComment('Reply Deleted', $parent, true);

        $repo    = $this->em->getRepository(Comment::class);
        $results = $repo->findReplies($parent, true)->getResult();

        $ids = array_map(fn(Comment $c) => $c->getId(), $results);
        $this->assertContains($activeReply->getId(), $ids);
        $this->assertContains($deletedReply->getId(), $ids);
    }

    public function testDeleteSetsDeletedAt(): void
    {
        $comment = $this->makeComment('To Delete');
        $id      = $comment->getId();

        $this->client->request('POST', "/{$id}/delete");

        $this->assertResponseRedirects('/');

        $this->em->clear();
        $refreshed = $this->em->find(Comment::class, $id);
        $this->assertNotNull($refreshed->getDeletedAt());
    }

    public function testDeleteReplyRedirectsToParentView(): void
    {
        $parent  = $this->makeComment('Parent');
        $reply   = $this->makeComment('Reply', $parent);
        $replyId = $reply->getId();

        $this->client->request('POST', "/{$replyId}/delete");

        $this->assertResponseRedirects('/' . $parent->getId() . '/view');
    }

    public function testEditPageLoads(): void
    {
        $comment = $this->makeComment('Editable');

        $this->client->request('GET', '/' . $comment->getId() . '/edit');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    public function testEditUpdatesComment(): void
    {
        $comment = $this->makeComment('Original Name');
        $id      = $comment->getId();

        $this->client->request('POST', "/{$id}/edit", [
            'comment' => [
                'name'    => 'Updated Name',
                'email'   => 'updated@example.com',
                'comment' => 'Updated body',
            ],
        ]);

        $this->assertResponseRedirects('/');

        $this->em->clear();
        $updated = $this->em->find(Comment::class, $id);
        $this->assertSame('Updated Name', $updated->getName());
    }

    public function testEditReplyRedirectsToParentView(): void
    {
        $parent = $this->makeComment('Parent');
        $reply  = $this->makeComment('Reply', $parent);
        $id     = $reply->getId();

        $this->client->request('POST', "/{$id}/edit", [
            'comment' => [
                'name'    => 'Edited Reply',
                'email'   => 'r@example.com',
                'comment' => 'Edited body',
            ],
        ]);

        $this->assertResponseRedirects('/' . $parent->getId() . '/view');
    }

    public function testHomepageShowsDeletedToggle(): void
    {
        $this->client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('a[href*="show_deleted"]');
    }

    public function testHomepageWithShowDeletedShowsHideButton(): void
    {
        $this->client->request('GET', '/?show_deleted=1');

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Hide Deleted', $this->client->getResponse()->getContent());
    }

    public function testViewPageShowsDeletedToggle(): void
    {
        $comment = $this->makeComment('Parent');

        $this->client->request('GET', '/' . $comment->getId() . '/view');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('a[href*="show_deleted"]');
    }
}
