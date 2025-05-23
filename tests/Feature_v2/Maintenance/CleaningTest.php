<?php

/**
 * SPDX-License-Identifier: MIT
 * Copyright (c) 2017-2018 Tobias Reich
 * Copyright (c) 2018-2025 LycheeOrg.
 */

/**
 * We don't care for unhandled exceptions in tests.
 * It is the nature of a test to throw an exception.
 * Without this suppression we had 100+ Linter warning in this file which
 * don't help anything.
 *
 * @noinspection PhpDocMissingThrowsInspection
 * @noinspection PhpUnhandledExceptionInspection
 */

namespace Tests\Feature_v2\Maintenance;

use function Safe\mkdir;
use function Safe\touch;
use Tests\Feature_v2\Base\BaseApiWithDataTest;

class CleaningTest extends BaseApiWithDataTest
{
	public function testGuest(): void
	{
		$response = $this->getJsonWithData('Maintenance::cleaning', []);
		$this->assertUnprocessable($response);

		$response = $this->getJsonWithData('Maintenance::cleaning', ['path' => 'filesystems.disks.extract-jobs.root']);
		$this->assertUnauthorized($response);

		$response = $this->postJson('Maintenance::cleaning');
		$this->assertUnprocessable($response);

		$response = $this->postJson('Maintenance::cleaning', ['path' => 'filesystems.disks.extract-jobs.root']);
		$this->assertUnauthorized($response);
	}

	public function testUser(): void
	{
		$response = $this->actingAs($this->userLocked)->getJsonWithData('Maintenance::cleaning');
		$this->assertUnprocessable($response);

		$response = $this->actingAs($this->userLocked)->getJsonWithData('Maintenance::cleaning', ['path' => 'filesystems.disks.extract-jobs.root']);
		$this->assertForbidden($response);

		$response = $this->actingAs($this->userLocked)->postJson('Maintenance::cleaning');
		$this->assertUnprocessable($response);

		$response = $this->actingAs($this->userLocked)->postJson('Maintenance::cleaning', ['path' => 'filesystems.disks.extract-jobs.root']);
		$this->assertForbidden($response);
	}

	public function testAdmin(): void
	{
		$response = $this->actingAs($this->admin)->getJsonWithData('Maintenance::cleaning');
		$this->assertUnprocessable($response);

		$response = $this->actingAs($this->admin)->getJsonWithData('Maintenance::cleaning', ['path' => 'filesystems.disks.extract-jobs.root']);
		$this->assertOk($response);

		$response = $this->actingAs($this->admin)->postJson('Maintenance::cleaning');
		$this->assertUnprocessable($response);

		$response = $this->actingAs($this->admin)->postJson('Maintenance::cleaning', ['path' => 'filesystems.disks.extract-jobs.root']);
		$this->assertOk($response);
	}

	public function testAdminWithFiles(): void
	{
		touch(storage_path('tmp/extract/') . '/delete-me.txt');
		mkdir(storage_path('tmp/extract/something'));
		$response = $this->actingAs($this->admin)->getJsonWithData('Maintenance::cleaning', ['path' => 'filesystems.disks.extract-jobs.root']);
		$this->assertOk($response);

		$response = $this->actingAs($this->admin)->postJson('Maintenance::cleaning', ['path' => 'filesystems.disks.extract-jobs.root']);
		$this->assertOk($response);
		static::assertEquals(false, file_exists(storage_path('extract-jobs') . '/delete-me.txt'));
	}
}