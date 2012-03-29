<?php
/**
 * Part of CoolMS Content Management System
 *
 * @copyright (c) 2011 Ondrej Slamecka (http://www.slamecka.cz)
 *
 * License within file license.txt in the root folder.
 *
 */

namespace Coolms\Entity;

class Menuitem extends \Nette\Object
{
    const TYPE_SUBMENU = 'submenu';
    const TYPE_MODULE = 'modulelink';
}