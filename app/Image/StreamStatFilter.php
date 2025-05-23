<?php

/**
 * SPDX-License-Identifier: MIT
 * Copyright (c) 2017-2018 Tobias Reich
 * Copyright (c) 2018-2025 LycheeOrg.
 */

namespace App\Image;

/**
 * Class `StreamStatFilter` collects {@link StreamStat} during streaming.
 */
class StreamStatFilter extends \php_user_filter
{
	public const REGISTERED_NAME = 'stream-stat-filter';

	/** @var string HASH_ALGO_NAME the used hashing algorithm; value must be supported by PHP, see {@link hash_algos()} */
	public const HASH_ALGO_NAME = 'sha1';

	/** @var \HashContext|null the hash context for progressive hashing */
	protected ?\HashContext $hash_context = null;

	/**
	 * Called to move streamed data from `$in` to `$out`.
	 *
	 * {@inheritDoc}
	 *
	 * Updates the byte counter and the hash.
	 */
	public function filter($in, $out, &$consumed, bool $closing): int
	{
		while (($bucket = stream_bucket_make_writeable($in)) !== null) {
			$consumed += intval($bucket->datalen);
			if ($this->params instanceof StreamStat) {
				$this->params->bytes += $bucket->datalen;
				\hash_update($this->hash_context, $bucket->data);
			}
			stream_bucket_append($out, $bucket);
		}

		return PSFS_PASS_ON;
	}

	/**
	 * Called when the stream is closed.
	 *
	 * {@inheritDoc}
	 *
	 * Finalizes the hash.
	 */
	public function onClose(): void
	{
		if ($this->params instanceof StreamStat) {
			$this->params->checksum = \hash_final($this->hash_context);
		}
		parent::onClose();
	}

	/**
	 * Called when the stream is opened.
	 *
	 * {@inheritDoc}
	 *
	 * Initializes the hash.
	 */
	public function onCreate(): bool
	{
		if ($this->params instanceof StreamStat) {
			$this->params->bytes = 0;
			$this->hash_context = \hash_init(self::HASH_ALGO_NAME);
		}

		return parent::onCreate();
	}
}