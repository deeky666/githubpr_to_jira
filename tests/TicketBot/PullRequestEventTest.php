<?php

namespace TicketBot;

class PullRequestEventTest extends \PHPUnit_Framework_TestCase
{
    public function testIsSynchronize()
    {
        $event = new PullRequestEvent(array('action' => 'synchronize', 'pull_request' => array('html_url' => 'http')));

        $this->assertTrue($event->isSynchronize());
        $this->assertFalse($event->isOpened());
    }

    public function testIsOpened()
    {
        $event = new PullRequestEvent(array('action' => 'opened', 'pull_request' => array('html_url' => 'http')));

        $this->assertFalse($event->isSynchronize());
        $this->assertTrue($event->isOpened());
    }

    public function testIssuePrefix()
    {
        $event = new PullRequestEvent(array('action' => 'synchronize', 'pull_request' => array('html_url' => 'https://github.com/doctrine/doctrine2/pulls/127')));
        $this->assertEquals('[GH-127]', $event->issuePrefix());
    }

    public function testMetadata()
    {
        $event = new PullRequestEvent(array(
            'action' => 'synchronize',
            'pull_request' => array(
                'html_url' => 'https://github.com/doctrine/doctrine2/pulls/127',
                'user' => array('login' => 'beberlei'),
                'title' => 'some title',
                'body' => 'some body',
            )
        ));

        $this->assertEquals('beberlei', $event->openerUsername());
        $this->assertEquals('some title', $event->title());
        $this->assertEquals('some body', $event->body());
    }

    public function testSearchTerms()
    {
        $event = new PullRequestEvent(array(
            'action' => 'synchronize',
            'pull_request' => array(
                'html_url' => 'https://github.com/doctrine/doctrine2/pulls/127',
                'user' => array('login' => 'beberlei'),
                'title' => '[DDC-1234] Doing foo with pride',
                'body' => 'Hello, talking about DDC-4567.',
            )
        ));

        $project = new JiraProject();
        $project->shortname = "DDC";

        $terms = $event->searchTerms($project);

        $this->assertEquals(array(
            'https://github.com/doctrine/doctrine2/pulls/127',
            'DDC-1234',
            'DDC-4567',
        ), $terms);
    }

    public function testRepositoryIssueId()
    {
        $event = new PullRequestEvent(array(
            'action' => 'synchronize',
            'pull_request' => array(
                'base' => array(
                    'repo' => array(
                        'name' => 'doctrine2',
                    ),
                ),
                'html_url' => 'https://github.com/doctrine/doctrine2/pulls/127',
            )
        ));

        $this->assertSame('doctrine2#127', $event->repositoryIssueId());
    }

    public function testBaseBranch()
    {
        $event = new PullRequestEvent(array(
            'action' => 'synchronize',
            'base' => array(
                'ref' => 'master',
            ),
            'pull_request' => array(
                'html_url' => 'https://github.com/doctrine/doctrine2/pulls/127',
            )
        ));

        $this->assertSame('master', $event->baseBranch());
    }

    public function testMergeCommitSha()
    {
        $event = new PullRequestEvent(array(
            'action' => 'closed',
            'pull_request' => array(
                'html_url' => 'https://github.com/doctrine/doctrine2/pulls/127',
                'merge_commit_sha' => '9049f1265b7d61be4a8904a9a27120d2064dab3b',
            )
        ));

        $this->assertSame('9049f1265b7d61be4a8904a9a27120d2064dab3b', $event->mergeCommitSha());
    }

    public function testMergeCommitUrl()
    {
        $event = new PullRequestEvent(array(
            'action' => 'closed',
            'base' => array(
                'repo' => array(
                    'html_url' => 'https://github.com/doctrine/doctrine2',
                ),
            ),
            'pull_request' => array(
                'html_url' => 'https://github.com/doctrine/doctrine2/pulls/127',
                'merge_commit_sha' => '9049f1265b7d61be4a8904a9a27120d2064dab3b',
                'merged' => true,
            )
        ));

        $this->assertSame('9049f1265b7d61be4a8904a9a27120d2064dab3b', $event->mergeCommitSha());
    }

    public function testMergedAt()
    {
        $event = new PullRequestEvent(array(
            'action' => 'closed',
            'pull_request' => array(
                'html_url' => 'https://github.com/doctrine/doctrine2/pulls/127',
                'merged_at' => '2015-05-05T23:40:27Z',
            )
        ));

        $this->assertSame('2015-05-05T23:40:27Z', $event->mergedAt());
    }

    public function testMergedBy()
    {
        $event = new PullRequestEvent(array(
            'action' => 'closed',
            'base' => array(
                'merged_by' => 'deeky666',
            ),
            'pull_request' => array(
                'html_url' => 'https://github.com/doctrine/doctrine2/pulls/127',
            )
        ));


        $this->assertSame('deeky666', $event->mergedBy());
    }
}
