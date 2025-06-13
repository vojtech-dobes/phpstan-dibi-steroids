<?php declare(strict_types=1);

namespace Vojtechdobes\PHPStan\Dibi\Query;


enum JoinType: string
{

	case Cross = 'CROSS';
	case Left = 'LEFT';
	case Main = 'JOIN';

}
