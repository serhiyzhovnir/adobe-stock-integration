<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeMediaGallery\Test\Unit\Model\Keyword\Command;

use Magento\AdobeMediaGallery\Model\Keyword\Command\GetAssetKeywords;
use Magento\AdobeMediaGalleryApi\Api\Data\KeywordInterface;
use Magento\AdobeMediaGalleryApi\Api\Data\KeywordInterfaceFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\Exception\NotFoundException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GetAssetKeywordsTest extends TestCase
{
    /**
     * @var GetAssetKeywords
     */
    private $sut;

    /**
     * @var ResourceConnection | MockObject
     */
    private $resourceConnectionStub;

    /**
     * @var KeywordInterfaceFactory | MockObject
     */
    private $assetKeywordFactoryStub;

    protected function setUp(): void
    {
        $this->resourceConnectionStub = $this->createMock(ResourceConnection::class);
        $this->assetKeywordFactoryStub = $this->createMock(KeywordInterfaceFactory::class);

        $this->sut = new GetAssetKeywords(
            $this->resourceConnectionStub,
            $this->assetKeywordFactoryStub
        );
    }

    /**
     * Posive test for the main case
     *
     * @dataProvider casesProvider()
     * @param array $databaseQueryResult
     * @param int $expectedNumberOfFoundKeywords
     * @throws NotFoundException
     */
    public function testFind(array $databaseQueryResult, int $expectedNumberOfFoundKeywords): void
    {
        $randomAssetId = 12345;
        $this->configureResourceConnectionStub($databaseQueryResult);
        $this->configureAssetKeywordFactoryStub();

        /** @var KeywordInterface[] $keywords */
        $keywords = $this->sut->execute($randomAssetId);

        $this->assertCount($expectedNumberOfFoundKeywords, $keywords);
    }

    /**
     * Data provider for testFind
     *
     * @return array
     */
    public function casesProvider(): array
    {
        return [
            'not_found' => [[],0],
            'find_one_keyword' => [['keywordRawData'],1],
            'find_several_keywords' => [['keywordRawData', 'keywordRawData'],2],
        ];
    }

    /**
     * Negative test
     *
     * @throws NotFoundException
     */
    public function testNotFoundBecauseOfError(): void
    {
        $randomAssetId = 1;

        $this->resourceConnectionStub
            ->method('getConnection')
            ->willThrowException((new \Exception()));

        $this->expectException(NotFoundException::class);

        $this->sut->execute($randomAssetId);
    }

    /**
     * Very fragile and coupled to the implementation
     *
     * @param array $queryResult
     */
    private function configureResourceConnectionStub(array $queryResult): void
    {
        $statementMock = $this->getMockBuilder(\Zend_Db_Statement_Interface::class)->getMock();
        $statementMock
            ->method('fetchAll')
            ->willReturn($queryResult);

        $selectStub = $this->createMock(Select::class);
        $selectStub->method('from')->willReturnSelf();
        $selectStub->method('join')->willReturnSelf();
        $selectStub->method('where')->willReturnSelf();

        $connectionMock = $this->getMockBuilder(AdapterInterface::class)->getMock();
        $connectionMock
            ->method('select')
            ->willReturn($selectStub);
        $connectionMock
            ->method('query')
            ->willReturn($statementMock);

        $this->resourceConnectionStub
            ->method('getConnection')
            ->willReturn($connectionMock);
    }

    private function configureAssetKeywordFactoryStub(): void
    {
        $keywordStub = $this->getMockBuilder(KeywordInterface::class)->getMock();
        $this->assetKeywordFactoryStub
            ->method('create')
            ->willReturn($keywordStub);
    }
}
