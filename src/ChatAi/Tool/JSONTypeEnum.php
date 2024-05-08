<?php

namespace Clair\Ai\ChatAi\Tool;

enum JSONTypeEnum :string
{
    case Array = 'array';

    case Boolean = 'boolean';

    case Integer = 'integer';

    case Number = 'number';

    case Null = 'null';

    case Object = 'object';

    case String = 'string';

}