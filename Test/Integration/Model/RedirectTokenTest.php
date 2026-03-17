<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is provided with Magento in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * See DISCLAIMER.md for disclaimer details.
 */

declare(strict_types=1);

namespace MultiSafepay\ConnectCore\Test\Integration\Model;

use Magento\Framework\Exception\AlreadyExistsException;
use MultiSafepay\ConnectCore\Test\Integration\AbstractTestCase;
use MultiSafepay\ConnectCore\Model\RedirectToken;
use MultiSafepay\ConnectCore\Model\ResourceModel\RedirectToken as RedirectTokenResource;
use Random\RandomException;

class RedirectTokenTest extends AbstractTestCase
{
    /**
     * Test that the model can be instantiated without errors.
     *
     * @return void
     */
    public function testModelCanBeCreated(): void
    {
        $model = $this->getObjectManager()->create(RedirectToken::class);
        self::assertInstanceOf(RedirectToken::class, $model);
    }

    /**
     * Test that saving a token and then loading it by ID returns the same data that was saved.
     *
     * @return void
     * @throws AlreadyExistsException
     * @throws RandomException
     */
    public function testSaveAndLoadPersistsData(): void
    {
        /** @var RedirectToken $model */
        $model = $this->getObjectManager()->create(RedirectToken::class);
        $token = bin2hex(random_bytes(16));

        $model->setToken($token);
        $model->setOrderIncrementId('100000001');
        $model->setExpired(false);

        /** @var RedirectTokenResource $resource */
        $resource = $this->getObjectManager()->get(RedirectTokenResource::class);
        $resource->save($model);

        $id = $model->getTokenId();

        self::assertNotNull($id);
        self::assertGreaterThan(0, $id);

        /** @var RedirectToken $loaded */
        $loaded = $this->getObjectManager()->create(RedirectToken::class);

        $resource->load($loaded, $id);

        self::assertSame($token, $loaded->getToken());
        self::assertSame('100000001', $loaded->getOrderIncrementId());
        self::assertFalse($loaded->isExpired());
        self::assertNotEmpty($loaded->getCreatedAt());
    }

    /**
     * Test that the expired flag is properly cast to a boolean when set and retrieved.
     *
     * @return void
     */
    public function testExpiredFlagIsCastToBool(): void
    {
        /** @var RedirectToken $model */
        $model = $this->getObjectManager()->create(RedirectToken::class);

        $model->setExpired(false);
        self::assertFalse($model->isExpired());

        $model->setExpired(true);
        self::assertTrue($model->isExpired());
    }
}
