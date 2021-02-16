<?php

namespace App\Model;

class JiraProject
{
    protected string $expand = '';
    protected string $self = '';
    protected string $id = '';
    protected string $key = '';
    protected string $name = '';
    protected array $avatarUrls = [];
    protected string $projectTypeKey = '';
    protected bool $simplified = false;
    protected string $style = '';
    protected bool $isPrivate = false;
    protected array $properties = [];
    protected string $entityId = '';
    protected string $uuid = '';
    /** @var JiraSprint[] */
    protected array $sprints = [];

    public function getExpand(): string
    {
        return $this->expand;
    }

    public function setExpand(string $expand): JiraProject
    {
        $this->expand = $expand;
        return $this;
    }

    public function getSelf(): string
    {
        return $this->self;
    }

    public function setSelf(string $self): JiraProject
    {
        $this->self = $self;
        return $this;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): JiraProject
    {
        $this->id = $id;
        return $this;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function setKey(string $key): JiraProject
    {
        $this->key = $key;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): JiraProject
    {
        $this->name = $name;
        return $this;
    }

    public function getAvatarUrls(): array
    {
        return $this->avatarUrls;
    }

    public function setAvatarUrls(array $avatarUrls): JiraProject
    {
        $this->avatarUrls = $avatarUrls;
        return $this;
    }

    public function getProjectTypeKey(): string
    {
        return $this->projectTypeKey;
    }

    public function setProjectTypeKey(string $projectTypeKey): JiraProject
    {
        $this->projectTypeKey = $projectTypeKey;
        return $this;
    }

    public function isSimplified(): bool
    {
        return $this->simplified;
    }

    public function setSimplified(bool $simplified): JiraProject
    {
        $this->simplified = $simplified;
        return $this;
    }

    public function getStyle(): string
    {
        return $this->style;
    }

    public function setStyle(string $style): JiraProject
    {
        $this->style = $style;
        return $this;
    }

    public function isPrivate(): bool
    {
        return $this->isPrivate;
    }

    public function setIsPrivate(bool $isPrivate): JiraProject
    {
        $this->isPrivate = $isPrivate;
        return $this;
    }

    public function getProperties(): array
    {
        return $this->properties;
    }

    public function setProperties(array $properties): JiraProject
    {
        $this->properties = $properties;
        return $this;
    }

    public function getEntityId(): string
    {
        return $this->entityId;
    }

    public function setEntityId(string $entityId): JiraProject
    {
        $this->entityId = $entityId;
        return $this;
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function setUuid(string $uuid): JiraProject
    {
        $this->uuid = $uuid;
        return $this;
    }

    public function getSprints(): array
    {
        return $this->sprints;
    }

    /**
     * @param JiraSprint[] $sprints
     */
    public function setSprints(array $sprints): JiraProject
    {
        $this->sprints = $sprints;
        return $this;
    }
}
