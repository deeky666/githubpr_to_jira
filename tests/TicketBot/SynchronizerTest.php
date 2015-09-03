<?php

namespace TicketBot;

class SynchronizerTest extends \PHPUnit_Framework_TestCase
{
    public function testOpenedPullRequest()
    {
        $event = $this->createPullRequestEvent('opened');

        $jira = \Phake::mock('TicketBot\Jira');
        $github = \Phake::mock('TicketBot\Github');
        $project = \Phake::mock('TicketBot\JiraProject');

        $issue = new JiraIssue();
        $link = new JiraRemoteLink('https://github.com/doctrine/doctrine2/pulls/127', 'Pull Request');
        $comment = <<<TEXT
Hello,

thank you for creating this pull request. I have automatically opened an issue
on our Jira Bug Tracker for you. See the issue link:

/browse/

We use Jira to track the state of pull requests and the versions they got
included in.
TEXT;

        \Phake::when($project)->createTicket(\Phake::anyParameters())->thenReturn(new NewJiraIssue('foo', 'bar'));
        \Phake::when($jira)->createIssue(\Phake::anyParameters())->thenReturn($issue);
        \Phake::when($project)->createPullRequestLink(\Phake::anyParameters())->thenReturn($link);
        \Phake::when($project)->createPullRequestLinkRelationship(\Phake::anyParameters())->thenReturn('relationship');
        \Phake::when($project)->createNotifyComment(\Phake::anyParameters())->thenReturn($comment);

        $synchronizer = new Synchronizer($jira, $github);
        $synchronizer->accept($event, $project);

        \Phake::verify($github)->addComment("bar", "foo", 127, $comment);
        \Phake::verify($jira)->addRemoteLink(
            $issue,
            $link,
            'relationship'
        );
    }

    public function testMergedPullRequest()
    {
        $event = $this->createPullRequestEvent('closed');

        $jira = \Phake::mock('TicketBot\Jira');
        $github = \Phake::mock('TicketBot\Github');
        $project = \Phake::mock('TicketBot\JiraProject');

        $comment = "A related Github Pull-Request [GH-127] was merged:\nhttps://github.com/doctrine/doctrine2/pulls/127";
        $link = new JiraRemoteLink('https://github.com/doctrine/doctrine2/commit/9049f1265b7d61be4a8904a9a27120d2064dab3b', 'Merge Commit');

        \Phake::when($jira)->search(\Phake::anyParameters())->thenReturn(array(
            $issue = JiraIssue::createFromArray(array("key" => "DDC-1234", "summary" => '[GH-127] Issue Summary'))
        ));
        \Phake::when($project)->createComment(\Phake::anyParameters())->thenReturn($comment);
        \Phake::when($project)->createMergeCommitLink(\Phake::anyParameters())->thenReturn($link);
        \Phake::when($project)->createPullRequestLinkRelationship(\Phake::anyParameters())->thenReturn('relationship');

        $synchronizer = new Synchronizer($jira, $github);
        $synchronizer->accept($event, $project);

        \Phake::verify($jira)->addComment($issue, $comment);
        \Phake::verify($jira)->addRemoteLink($issue, $link, 'relationship');
    }

    public function testClosedPullRequest()
    {
        $event = $this->createPullRequestEvent('closed', false);

        $jira = \Phake::mock('TicketBot\Jira');
        $github = \Phake::mock('TicketBot\Github');

        $project = new JiraProject();

        \Phake::when($jira)->search(\Phake::anyParameters())->thenReturn(array(
            $issue = JiraIssue::createFromArray(array("key" => "DDC-1234"))
        ));

        $synchronizer = new Synchronizer($jira, $github);
        $synchronizer->accept($event, $project);

        \Phake::verify($jira)->addComment($issue, "A related Github Pull-Request [GH-127] was closed:\nhttps://github.com/doctrine/doctrine2/pulls/127");
    }

    private function createPullRequestEvent($action, $merged = true)
    {
        $event = new PullRequestEvent(array(
            'action' => $action,
            'pull_request' => array(
                'html_url' => 'https://github.com/doctrine/doctrine2/pulls/127',
                'user' => array('login' => 'beberlei'),
                'title' => 'some title',
                'body' => 'some body',
                'base' => array(
                    'ref' => 'master',
                    'repo' => array('name' => 'foo', 'owner' => array('login' => 'bar')),
                ),
                'merged' => $merged,
            )
        ));

        return $event;
    }
}
