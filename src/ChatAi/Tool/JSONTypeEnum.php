<?php

namespace Clair\Ai\ChatAi\Tool;

/**
 *  case JSONのtype : PHPの型
 */
enum JSONTypeEnum :string
{
    case Array = 'array';

    case Boolean = 'bool';

    case Integer = 'int';

    case Number = 'float';

    case Null = 'null';

    case Object = 'object';

    case String = 'string';

    public function getJsonType(): string
    {
        return strtolower($this->name);
    }

}