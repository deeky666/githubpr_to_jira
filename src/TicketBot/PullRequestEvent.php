<?php

namespace TicketBot;

class PullRequestEvent
{
    private $event;

    public function __construct(array $event)
    {
        if (!isset($event['action'])) {
            throw new \RuntimeException("Missing action in Pull Request");
        }
        if (!isset($event['pull_request']['html_url'])) {
            throw new \RuntimeException("Missing html url in Pull Request");
        }

        $this->event = $event;
    }

    public function isSendToMaster()
    {
        return $this->event['pull_request']['base']['ref'] === "master";
    }

    public function repository()
    {
        return $this->event['pull_request']['base']['repo']['name'];
    }

    public function owner()
    {
        return $this->event['pull_request']['base']['repo']['owner']['login'];
    }

    public function isSynchronize()
    {
        return $this->event['action'] === "synchronize";
    }

    public function isOpened()
    {
        return $this->event['action'] === "opened";
    }

    public function isClosed()
    {
        return $this->event['action'] === "closed";
    }

    public function isReopened()
    {
        return $this->event['action'] == "reopened";
    }

    public function issueUrl()
    {
        return $this->event['pull_request']['html_url'];
    }

    public function openerUsername()
    {
        return $this->event['pull_request']['user']['login'];
    }

    public function action()
    {
        if ($this->isClosed() && $this->isMerged()) {
            return 'merged';
        }

        return $this->event['action'];
    }

    public function getId()
    {
        $issueUrl = $this->issueUrl();
        $parts = explode("/", $issueUrl);
        $pullRequestId = array_pop($parts);

        return $pullRequestId;
    }

    public function repositoryIssueId()
    {
        return $this->repository() . '#' . $this->getId();
    }

    public function issuePrefix()
    {
        return "[GH-".$this->getId()."]";
    }

    public function title()
    {
        return $this->event['pull_request']['title'];
    }

    public function body()
    {
        return $this->event['pull_request']['body'];
    }

    public function searchTerms(JiraProject $project)
    {
        $issueSearchTerms = array($this->issueUrl());

        if (preg_match_all('((' . preg_quote($project->shortname) . '\-[0-9]+))', $this->title() . " " . $this->body(), $matches)) {
            $issueSearchTerms = array_merge($issueSearchTerms, array_values(array_unique($matches[1])));
        }

        return $issueSearchTerms;
    }

    public function baseBranch()
    {
        return $this->event['base']['ref'];
    }

    public function isMerged()
    {
        return $this->event['pull_request']['merged'];
    }

    public function mergeCommitSha()
    {
        return $this->event['pull_request']['merge_commit_sha'];
    }

    public function mergeCommitUrl()
    {
        if ($this->isMerged()) {
            return sprintf(
                '%s/commit/%s',
                $this->event['base']['repo']['html_url'],
                $this->event['pull_request']['merge_commit_sha']
            );
        }

        return null;
    }

    public function mergedAt()
    {
        return $this->event['pull_request']['merged_at'];
    }

    public function mergedBy()
    {
        return $this->event['base']['merged_by'];
    }
}
