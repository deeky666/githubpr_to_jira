<?php

namespace TicketBot;

class JiraProjectTest extends \PHPUnit_Framework_TestCase
{
    public function testCreateTicket()
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

        $project = new JiraProject();
        $issue = $project->createTicket($event);

        $this->assertInstanceOf('TicketBot\NewJiraIssue', $issue);
        $this->assertEquals('[GH-127] some title', $issue->title);
        $this->assertEquals(<<<ASSERT
This issue is created automatically through a Github pull request on behalf of beberlei:

Url: https://github.com/doctrine/doctrine2/pulls/127

Message:

some body

ASSERT
            , $issue->body);
    }

    public function testCreateComment()
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

        $project = new JiraProject();
        $comment = $project->createComment($event);

        $this->assertEquals("A related Github Pull-Request [GH-127] was synchronize:\nhttps://github.com/doctrine/doctrine2/pulls/127", $comment);
    }

    public function testCreatePullRequestLink()
    {
        $event = new PullRequestEvent(array(
            'action' => 'opened',
            'pull_request' => array(
                'html_url' => 'https://github.com/doctrine/doctrine2/pulls/127',
                'title' => 'Fix some issue',
            )
        ));

        $project = new JiraProject();
        $link = $project->createPullRequestLink($event);
        $expected = new JiraRemoteLink(
            'https://github.com/doctrine/doctrine2/pulls/127',
            'Pull Request',
            'Fix some issue'
        );

        $this->assertEquals($expected, $link);
    }

    public function testCreateMergeCommitLink()
    {
        $event = new PullRequestEvent(array(
            'action' => 'closed',
            'base' => array(
                'merged_by' => 'deeky666',
                'ref' => 'master',
                'repo' => array(
                    'html_url' => 'https://github.com/doctrine/doctrine2',
                )
            ),
            'pull_request' => array(
                'html_url' => 'https://github.com/doctrine/doctrine2/pulls/127',
                'merge_commit_sha' => '9049f1265b7d61be4a8904a9a27120d2064dab3b',
                'merged' => true,
                'merged_at' => '2015-05-05T23:40:27Z',
            )
        ));

        $project = new JiraProject();
        $link = $project->createMergeCommitLink($event);
        $expected = new JiraRemoteLink(
            'https://github.com/doctrine/doctrine2/commit/9049f1265b7d61be4a8904a9a27120d2064dab3b',
            'Merge Commit',
            'deeky666 merged commit 9049f1265b7d61be4a8904a9a27120d2064dab3b into master at 2015-05-05T23:40:27Z'
        );

        $this->assertEquals($expected, $link);
    }

    public function testCreatePullRequestLinkRelationship()
    {
        $event = new PullRequestEvent(array(
            'action' => 'closed',
            'pull_request' => array(
                'base' => array(
                    'repo' => array(
                        'name' => 'doctrine2',
                    ),
                ),
                'html_url' => 'https://github.com/doctrine/doctrine2/pulls/127',
            )
        ));

        $project = new JiraProject();

        $this->assertSame('relates to PR doctrine2#127', $project->createPullRequestLinkRelationship($event));
    }
}
