<?php declare(strict_types=1);

/**
 * @template TString of string
 */
final class StringableName
{

	/**
	 * @param TString $name
	 */
	public function __construct(
		private readonly string $name,
	) {}



	/**
	 * @return TString
	 */
	public function __toString(): string
	{
		return $this->name;
	}

}
