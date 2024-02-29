<?php

namespace Raxm;

use Axm;
use Raxm\Raxm;
use Raxm\ComponentCheckSum;
use Raxm\ComponentProperties;

/**
 * Class LifecycleManager
 *
 * This class extends Raxm and provides methods for managing 
 * the lifecycle of a component.
 */
class LifecycleManager extends Raxm
{
	protected static $id;
	protected static $initialData;
	protected static $effects = [];
	public static $initialresponse;

	/**
	 * Generate the initial fingerprint for the component.
	 * @return array The initial fingerprint.
	 */
	public static function initialFingerprint(): array
	{
		$app  = Axm::app();
		return [
			'id'     => hash('sha256', random_bytes(16)),
			'name'   => strtolower(self::componentName()),
			'locale' => 'EN',
			'path'   => $app->request->getUri(),
			'method' => $app->request->getMethod()
		];
	}

	/**
	 * Generate the initial effects for the component.
	 * @return array The initial effects.
	 */
	public static function initialEffects(): array
	{
		return [
			'listeners' => []
		];
	}

	/**
	 * Create the data server memo for the component.
	 * @return array The data server memo.
	 */
	public static function createDataServerMemo(): array
	{
		$checksum = [
			'checksum' => ComponentCheckSum::generate(
				static::initialFingerprint(),
				static::initialServerMemo()
			)
		];

		return array_merge(static::initialServerMemo(), $checksum);
	}

	/**
	 * Generate the initial server memo for the component.
	 * @return array The initial server memo.
	 */
	public static function initialServerMemo(): array
	{
		return [
			'children' => [],
			'errors'   => [],
			'htmlHash' => hash('sha256', random_bytes(16)),
			'data'     => static::addDataToInitialResponse(),
			'dataMeta' => [],
		];
	}

	/**
	 * Add data to the initial response.
	 * @return array The initial response with added data.
	 */
	public static function addDataToInitialResponse(): array
	{
		$properties = ComponentProperties::getPublicProperties(
			static::getInstanceNowComponent()
		) ?? [];

		return $properties;
	}
}
