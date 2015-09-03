<?php

namespace TicketBot;

interface Jira
{
    public function search(JiraProject $project, array $terms);
    public function createIssue(JiraProject $project, NewJiraIssue $newIssue);
    public function addComment(JiraIssue $issue, $comment);
    public function addRemoteLink(JiraIssue $issue, JiraRemoteLink $remoteLink, $relationship = null);
    public function resolveIssue(JiraIssue $issue);
    public function markIssueInvalid(JiraIssue $issue);
}
