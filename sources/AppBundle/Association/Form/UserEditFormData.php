<?php

namespace AppBundle\Association\Form;

use AppBundle\Association\Model\User;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class UserEditFormData
{
    public $companyId;
    /**
     * @var Assert\NotBlank()
     * @var Assert\Choice(choices={"M.", "Mme", "Mlle"}, strict=true)
     */
    public $civility = 'M.';
    /**
     * @Assert\NotBlank()
     */
    public $lastname;
    /**
     * @Assert\NotBlank()
     * @Assert\Length(max=40)
     */
    public $firstname;
    /**
     * @Assert\NotBlank()
     * @Assert\Email()
     * @Assert\Length(max=100)
     */
    public $email;
    /**
     * @Assert\Email()
     * @Assert\Length(max=100)
     */
    public $alternateEmail;
    /**
     * @Assert\NotBlank()
     */
    public $address;
    /**
     * @Assert\NotBlank()
     * @Assert\Length(min=4, max=10)
     */
    public $zipcode;
    /**
     * @Assert\NotBlank()
     * @Assert\Length(max=50)
     */
    public $city;
    /** @var string */
    public $countryId = 'FR';
    /**
     * @Assert\Length(max=20)
     */
    public $phone;
    /**
     * @Assert\Length(max=20)
     */
    public $cellphone;
    /**
     * @Assert\Choice(choices={0, 1, 2}, strict=true)
     */
    public $level = User::LEVEL_MEMBER;
    /**
     */
    public $directoryLevel = User::LEVEL_MEMBER;
    /**
     * @Assert\Choice(choices={0, 2}, strict=true)
     */
    public $eventLevel = User::LEVEL_MEMBER;
    /**
     * @Assert\Choice(choices={0, 2}, strict=true)
     */
    public $websiteLevel = User::LEVEL_MEMBER;
    /**
     * @Assert\Choice(choices={0, 2}, strict=true)
     */
    public $officeLevel = User::LEVEL_MEMBER;
    /**
     * @Assert\Choice(choices={0, 1, -1}, strict=true)
     */
    public $status = User::STATUS_INACTIVE;
    /**
     * @Assert\NotBlank()
     * @Assert\Length(max=30)
     */
    public $login;
    /**
     * @Assert\Length(max=30)
     */
    public $password;
    public $passwordConfirm;
    public $roles;
    /** @var bool */
    public $needsUpToDateMembership;

    /**
     * @Assert\Callback
     */
    public function validate(ExecutionContextInterface $context)
    {
        if ($this->password !== $this->passwordConfirm) {
            $context->buildViolation('Les mots de passe ne correspondent pas')
                ->atPath('password')
                ->addViolation();
        }

        if (!is_array(json_decode($this->roles, true))) {
            $context->buildViolation('Les roles ne sont pas valides')
                ->atPath('roles')
                ->addViolation();
        }
    }
}
