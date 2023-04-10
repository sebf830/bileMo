<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\UserRepository;
use JMS\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`users`')]
#[UniqueEntity(
    fields: ['username'],
    message: 'The email address already exists',
)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['userItem'])]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Email(message: 'Email is not valid')]
    #[Groups(['userItem'])]
    private ?string $username = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 60, minMessage: '2 characters minimum',maxMessage: '60 characters maximum')]
    #[Groups(['userItem'])]
    private ?string $firstname = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 60, minMessage: '2 characters minimum',maxMessage: '60 characters maximum')]
    #[Groups(['userItem'])]
    private ?string $lastname = null;

    #[ORM\Column]
    #[Groups(['userItem'])]
    private array $roles = [];

    #[ORM\Column(length: 255, nullable:true)]
    #[Groups(['userItem'])]
    private ?string $address = null;

    #[ORM\Column(length: 255, nullable:true)]
    #[Groups(['userItem'])]
    private ?string $zip = null;

    #[ORM\Column(length: 255, nullable:true)]
    #[Groups(['userItem'])]
    private ?string $city = null;

    #[ORM\Column(length: 255, nullable:true)]
    #[Groups(['userItem'])]
    private ?string $phone = null;

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    #[Assert\Regex(
        pattern: "/^(?=.*[a-zA-Z])(?=.*\\d).{8,}$/i",
        match: true,
        message: "password must contains at least 8 chars & include at least one uppercase and one number.")]
    private ?string $password = null;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'clientUsers')]
    private ?self $parent = null;

    #[ORM\OneToMany(mappedBy: 'parent', targetEntity: self::class)]
    private Collection $clientUsers;


    public function __construct()
    {
        $this->clientUsers = new ArrayCollection();
    }


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): self
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(string $lastname): self
    {
        $this->lastname = $lastname;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->username;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(string $address): self
    {
        $this->address = $address;

        return $this;
    }

    public function getZip(): ?string
    {
        return $this->zip;
    }

    public function setZip(string $zip): self
    {
        $this->zip = $zip;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(string $city): self
    {
        $this->city = $city;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): self
    {
        $this->phone = $phone;

        return $this;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent): self
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getClientUsers(): Collection
    {
        return $this->clientUsers;
    }

    public function addClientUser(self $clientUser): self
    {
        if (!$this->clientUsers->contains($clientUser)) {
            $this->clientUsers->add($clientUser);
            $clientUser->setParent($this);
        }

        return $this;
    }

    public function removeClientUser(self $clientUser): self
    {
        if ($this->clientUsers->removeElement($clientUser)) {
            // set the owning side to null (unless already changed)
            if ($clientUser->getParent() === $this) {
                $clientUser->setParent(null);
            }
        }

        return $this;
    }

}
