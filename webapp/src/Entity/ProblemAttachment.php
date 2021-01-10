<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @ORM\Entity()
 * @ORM\Table(
 *     name="problem_attachment",
 *     options={"collate"="utf8mb4_unicode_ci", "charset"="utf8mb4","comment"="Attachments belonging to problems"},
 *     indexes={
 *         @ORM\Index(name="name", columns={"attachmentid", "name"}, options={"lengths": {null, 190}})
 *     })
 */
class ProblemAttachment
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer", options={"comment"="Attachment ID","unsigned"="true"})
     */
    private $attachmentid;

    /**
     * @ORM\Column(type="string", length=255, options={"comment"="Filename of attachment"})
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=4, options={"comment"="File type of attachment"})
     */
    private $type;

    /**
     * @ORM\ManyToOne(targetEntity=Problem::class, inversedBy="attachments")
     * @ORM\JoinColumn(name="probid", referencedColumnName="probid", onDelete="CASCADE")
     */
    private $problem;

    /**
     * We use a OneToMany instead of a OneToOne here, because otherwise this
     * relation will always be loaded. See the commit message of commit
     * 9e421f96691ec67ed62767fe465a6d8751edd884 for a more elaborate explanation
     *
     * @var ProblemAttachmentContent[]|ArrayCollection
     * @ORM\OneToMany(targetEntity=ProblemAttachmentContent::class, mappedBy="attachment", cascade={"persist"}, orphanRemoval=true)
     */
    private $content;

    public function __construct()
    {
        $this->content = new ArrayCollection();
    }

    public function getAttachmentid(): ?int
    {
        return $this->attachmentid;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getProblem(): ?Problem
    {
        return $this->problem;
    }

    public function setProblem(?Problem $problem): self
    {
        $this->problem = $problem;

        return $this;
    }

    public function setContent(ProblemAttachmentContent $content): self
    {
        $this->content->clear();
        $this->content->add($content);
        $content->setAttachment($this);

        return $this;
    }

    /**
     * Get content
     *
     * @return ProblemAttachmentContent
     */
    public function getContent(): ?ProblemAttachmentContent
    {
        return $this->content->first() ?: null;
    }

    public function getStreamedResponse(): StreamedResponse
    {
        $content  = $this->getContent()->getContent();
        $filename = $this->getName();

        $response = new StreamedResponse();
        $response->setCallback(function () use ($content) {
            echo $content;
        });
        $response->headers->set('Content-Type',
            sprintf('application/octet-stream; name="%s', $filename));
        $response->headers->set('Content-Disposition',
            sprintf('attachment; filename="%s"', $filename));
        $response->headers->set('Content-Length', strlen($content));

        return $response;
    }
}
