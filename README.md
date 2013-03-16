doctrine-enum
=============

Enum type for Doctrine2

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
     * @Enum(class="Expensa\Enum\UserState")
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
