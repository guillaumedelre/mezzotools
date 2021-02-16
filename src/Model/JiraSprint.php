<?php

namespace App\Model;

class JiraSprint
{
    protected int $id = 0;
    protected string $self = '';
    protected string $state = '';
    protected string $name = '';
    protected \DateTime $startDate;
    protected \DateTime $endDate;
    protected \DateTime $completeDate;
    protected int $originBoardId = 0;
    protected string $goal = '';

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): JiraSprint
    {
        $this->id = $id;
        return $this;
    }

    public function getSelf(): string
    {
        return $this->self;
    }

    public function setSelf(string $self): JiraSprint
    {
        $this->self = $self;
        return $this;
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function setState(string $state): JiraSprint
    {
        $this->state = $state;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): JiraSprint
    {
        $this->name = $name;
        return $this;
    }

    public function getStartDate(): \DateTime
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTime $startDate): JiraSprint
    {
        $this->startDate = $startDate;
        return $this;
    }

    public function getEndDate(): \DateTime
    {
        return $this->endDate;
    }

    public function setEndDate(\DateTime $endDate): JiraSprint
    {
        $this->endDate = $endDate;
        return $this;
    }

    public function getCompleteDate(): \DateTime
    {
        return $this->completeDate;
    }

    public function setCompleteDate(\DateTime $completeDate): JiraSprint
    {
        $this->completeDate = $completeDate;
        return $this;
    }

    public function getOriginBoardId(): int
    {
        return $this->originBoardId;
    }

    public function setOriginBoardId(int $originBoardId): JiraSprint
    {
        $this->originBoardId = $originBoardId;
        return $this;
    }

    public function getGoal(): string
    {
        return $this->goal;
    }

    public function setGoal(string $goal): JiraSprint
    {
        $this->goal = $goal;
        return $this;
    }
}
