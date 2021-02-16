<?php

namespace App\Model;

class JiraCurrentSprint
{
    protected int $id = 0;
    protected \DateTime $start;
    protected \DateTime $end;
    protected string $name = '';
    protected bool $closed = false;
    protected bool $editable = false;
    protected array $projects = [];
    protected string $viewBoardsUrl = '';

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): JiraCurrentSprint
    {
        $this->id = $id;
        return $this;
    }

    public function getStart(): \DateTime
    {
        return $this->start;
    }

    public function setStart(\DateTime $start): JiraCurrentSprint
    {
        $this->start = $start;
        return $this;
    }

    public function getEnd(): \DateTime
    {
        return $this->end;
    }

    public function setEnd(\DateTime $end): JiraCurrentSprint
    {
        $this->end = $end;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): JiraCurrentSprint
    {
        $this->name = $name;
        return $this;
    }

    public function isClosed(): bool
    {
        return $this->closed;
    }

    public function setClosed(bool $closed): JiraCurrentSprint
    {
        $this->closed = $closed;
        return $this;
    }

    public function isEditable(): bool
    {
        return $this->editable;
    }

    public function setEditable(bool $editable): JiraCurrentSprint
    {
        $this->editable = $editable;
        return $this;
    }

    public function getProjects(): array
    {
        return $this->projects;
    }

    public function setProjects(array $projects): JiraCurrentSprint
    {
        $this->projects = $projects;
        return $this;
    }

    public function getViewBoardsUrl(): string
    {
        return $this->viewBoardsUrl;
    }

    public function setViewBoardsUrl(string $viewBoardsUrl): JiraCurrentSprint
    {
        $this->viewBoardsUrl = $viewBoardsUrl;
        return $this;
    }
}
