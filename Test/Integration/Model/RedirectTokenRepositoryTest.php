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

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use MultiSafepay\ConnectCore\Api\Data\RedirectTokenInterface;
use MultiSafepay\ConnectCore\Model\RedirectToken;
use MultiSafepay\ConnectCore\Model\RedirectTokenRepository;
use MultiSafepay\ConnectCore\Test\Integration\AbstractTestCase;
use Random\RandomException;

/**
 * @magentoDbIsolation enabled
 */
class RedirectTokenRepositoryTest extends AbstractTestCase
{
    /**
     * @var RedirectTokenRepository
     */
    private $repository;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->repository = $this->getObjectManager()->create(RedirectTokenRepository::class);
    }

    /**
     * Test that saving a token and then loading it by ID returns the same data that was saved.
     *
     * @return void
     * @throws NoSuchEntityException
     * @throws CouldNotSaveException
     * @throws RandomException
     */
    public function testSaveAndGetById(): void
    {
        $token = $this->createTokenModel();

        $saved = $this->repository->save($token);

        self::assertNotNull($saved->getTokenId());
        self::assertGreaterThan(0, (int) $saved->getTokenId());

        $loaded = $this->repository->getById((int) $saved->getTokenId());

        self::assertSame($saved->getTokenId(), $loaded->getTokenId());
        self::assertSame($token->getOrderIncrementId(), $loaded->getOrderIncrementId());
        self::assertSame($token->getToken(), $loaded->getToken());
        self::assertFalse($loaded->isExpired());
        self::assertNotEmpty($loaded->getCreatedAt());
    }

    /**
     * Test that getByToken returns the most recent token matching the given token value
     *
     * @return void
     * @throws CouldNotSaveException
     * @throws NoSuchEntityException
     * @throws LocalizedException
     * @throws RandomException
     */
    public function testGetByTokenReturnsMostRecentMatch(): void
    {
        $orderIncrementId = '100000002';

        $firstTokenValue = bin2hex(random_bytes(16));
        $secondTokenValue = bin2hex(random_bytes(16));

        $this->repository->create($orderIncrementId, $firstTokenValue);
        $second = $this->repository->create($orderIncrementId, $secondTokenValue);

        $loaded = $this->repository->getByToken($secondTokenValue);

        self::assertSame($second->getTokenId(), $loaded->getTokenId());
        self::assertSame($secondTokenValue, $loaded->getToken());
    }

    /**
     * Test that creating a new token for the same order increment ID expires any previous active tokens for the order.
     *
     * @return void
     * @throws CouldNotSaveException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws RandomException
     */
    public function testCreateExpiresPreviousActiveTokensForSameOrderIncrementId(): void
    {
        $orderIncrementId = '100000003';

        $firstTokenValue = bin2hex(random_bytes(16));
        $secondTokenValue = bin2hex(random_bytes(16));

        $first = $this->repository->create($orderIncrementId, $firstTokenValue);
        self::assertFalse($first->isExpired());

        $second = $this->repository->create($orderIncrementId, $secondTokenValue);
        self::assertFalse($second->isExpired());

        $reloadedFirst = $this->repository->getById((int)$first->getTokenId());
        self::assertTrue($reloadedFirst->isExpired());
    }

    /**
     * Test that getByOrderIncrementId returns the most recent active token for the given order increment ID.
     *
     * @return void
     * @throws CouldNotSaveException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws RandomException
     */
    public function testGetByOrderIncrementIdReturnsMostRecentActive(): void
    {
        $orderIncrementId = '100000004';

        $firstTokenValue = bin2hex(random_bytes(16));
        $secondTokenValue = bin2hex(random_bytes(16));

        $this->repository->create($orderIncrementId, $firstTokenValue);
        $second = $this->repository->create($orderIncrementId, $secondTokenValue);
        $active = $this->repository->getByOrderIncrementId($orderIncrementId);

        self::assertSame($second->getTokenId(), $active->getTokenId());
        self::assertSame($secondTokenValue, $active->getToken());
        self::assertFalse($active->isExpired());
    }

    /**
     * Test that getByToken throws an exception when no token is found for the given token value.
     *
     * @return void
     * @throws NoSuchEntityException
     * @throws RandomException
     */
    public function testGetByTokenThrowsExceptionWhenNotFound(): void
    {
        $this->expectException(NoSuchEntityException::class);
        $this->repository->getByToken(bin2hex(random_bytes(16)));
    }

    /**
     * Test that getByOrderIncrementId throws an exception when no active token is found for given order increment ID.
     *
     * @return void
     * @throws NoSuchEntityException
     */
    public function testGetByOrderIncrementIdThrowsExceptionWhenNoActiveTokenFound(): void
    {
        $this->expectException(NoSuchEntityException::class);
        $this->repository->getByOrderIncrementId('100000099');
    }

    /**
     * Test that getOrderIncrementIdByToken returns the correct order increment ID for a given token.
     *
     * @return void
     * @throws CouldNotSaveException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws RandomException
     */
    public function testGetOrderIncrementIdByTokenReturnsValue(): void
    {
        $orderIncrementId = '100000005';
        $tokenValue = bin2hex(random_bytes(16));

        $this->repository->create($orderIncrementId, $tokenValue);

        $resolvedIncrementId = $this->repository->getOrderIncrementIdByToken($tokenValue);
        self::assertSame($orderIncrementId, $resolvedIncrementId);
    }

    /**
     * Test that getOrderIncrementIdByToken throws an exception when the token does not exist.
     *
     * @return void
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws RandomException
     */
    public function testGetOrderIncrementIdByTokenThrowsExceptionWhenTokenDoesNotExist(): void
    {
        $this->expectException(NoSuchEntityException::class);
        $this->repository->getOrderIncrementIdByToken(bin2hex(random_bytes(16)));
    }

    /**
     * Helper to create a token model instance without triggering expire logic.
     *
     * @return RedirectTokenInterface
     * @throws RandomException
     */
    private function createTokenModel(): RedirectTokenInterface
    {
        /** @var RedirectTokenInterface $model */
        $model = $this->getObjectManager()->create(RedirectToken::class);

        $model->setOrderIncrementId('100000001');
        $model->setToken(bin2hex(random_bytes(16)));
        $model->setExpired(false);

        return $model;
    }
}
