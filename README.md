doctrine-enum
=============

Enum type for Doctrine2

Example
-
Register subscriber:
```php
$eventManager->addEventSubscriber(new Enum\Doctrine\EnumSubscriber());
```

Define enum:
```php
<?php

namespace App\Enum;

use Enum\AbstractEnum;

class UserState extends AbstractEnum
{
    const STATE_NEW = 'new';
    const STATE_ACTIVE = 'active';
}
```
Define entity:
```php
<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Enum\UserState;
use Enum\Doctrine\Mapping\Annotation\Enum;

/**
 * @ORM\Entity
 **/
class User
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     **/
    protected $id;

    /**
     * @Enum(class="App\Enum\UserState")
     * @ORM\Column(type="string_enum", length=50)
     **/
    protected $state;

    public function __construct()
    {
        // you can set default
        $this->state = new UserState(UserState::STATE_NEW);
    }

    /**
     * @param UserState $state
     */
    public function setState(UserState $state)
    {
        $this->state = $state;
    }

    /**
     * @return UserState
     */
    public function getState()
    {
        return $this->state;
    }
}
```

Work with enums:
```php
<?php

use App\Entity\User;
use App\Enum\UserState;

$user = $entityManager->getRepository('App\Entity\User')->find(1);
echo $user->getState()->getValue();
// new
$user->setState(new UserState(UserState::STATE_ACTIVE));
echo $user->getState()->getValue();
// active
$entityManager->flush($user);
```
