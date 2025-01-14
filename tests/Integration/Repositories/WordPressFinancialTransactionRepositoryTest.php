<?php
namespace CondMan\Tests\Integration\Repositories;

use PHPUnit\Framework\TestCase;
use CondMan\Domain\Entities\FinancialTransaction;
use CondMan\Infrastructure\Repositories\WordPressFinancialTransactionRepository;
use CondMan\Domain\Interfaces\LoggerInterface;
use DateTime;
use wpdb;

class WordPressFinancialTransactionRepositoryTest extends TestCase {
    private WordPressFinancialTransactionRepository $repository;
    private wpdb $wpdb;
    private LoggerInterface $logger;
    private string $testTableName;

    protected function setUp(): void {
        global $wpdb;
        $this->wpdb = $wpdb;
        
        // Criar logger mock
        $this->logger = $this->createMock(LoggerInterface::class);
        
        // Nome da tabela de teste
        $this->testTableName = $this->wpdb->prefix . 'financial_transactions_test';
        
        // Criar tabela de teste
        $this->createTestTable();
        
        // Inicializar repositório
        $this->repository = new WordPressFinancialTransactionRepository(
            $this->wpdb, 
            $this->logger, 
            $this->testTableName
        );
    }

    protected function tearDown(): void {
        // Limpar tabela de teste
        $this->wpdb->query("DROP TABLE IF EXISTS {$this->testTableName}");
    }

    private function createTestTable(): void {
        $charset_collate = $this->wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE {$this->testTableName} (
            id BIGINT(20) NOT NULL AUTO_INCREMENT,
            condominium_id BIGINT(20) NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            type ENUM('revenue', 'expense') NOT NULL,
            category VARCHAR(100) NOT NULL,
            description TEXT,
            date DATETIME NOT NULL,
            status ENUM('pending', 'completed', 'cancelled', 'overdue') NOT NULL,
            invoice_id BIGINT(20),
            payment_id BIGINT(20),
            reference VARCHAR(255),
            metadata JSON,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) {$charset_collate};";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function testSaveAndFindTransaction(): void {
        // Criar transação de teste
        $transaction = new FinancialTransaction(
            null,
            1,  // condominium_id
            1000.00,  // amount
            'revenue',  // type
            'aluguel',  // category
            'Pagamento de aluguel',  // description
            new DateTime('2024-01-15'),  // date
            'completed',  // status
            null,  // invoice_id
            null,  // payment_id
            'REF001',  // reference
            ['tenant' => 'John Doe']  // metadata
        );

        // Salvar transação
        $savedTransaction = $this->repository->save($transaction);

        // Verificar se a transação foi salva com sucesso
        $this->assertNotNull($savedTransaction->getId());

        // Buscar transação salva
        $foundTransaction = $this->repository->findById($savedTransaction->getId());

        // Verificações
        $this->assertNotNull($foundTransaction);
        $this->assertEquals(1, $foundTransaction->getCondominiumId());
        $this->assertEquals(1000.00, $foundTransaction->getAmount());
        $this->assertEquals('revenue', $foundTransaction->getType());
        $this->assertEquals('aluguel', $foundTransaction->getCategory());
        $this->assertEquals('Pagamento de aluguel', $foundTransaction->getDescription());
        $this->assertEquals('completed', $foundTransaction->getStatus());
        $this->assertEquals('REF001', $foundTransaction->getReference());
        $this->assertEquals(['tenant' => 'John Doe'], $foundTransaction->getMetadata());
    }

    public function testFindByFilters(): void {
        // Criar múltiplas transações de teste
        $transactions = [
            new FinancialTransaction(
                null, 1, 1000.00, 'revenue', 'aluguel', 
                'Aluguel Jan', new DateTime('2024-01-15'), 'completed'
            ),
            new FinancialTransaction(
                null, 1, 500.00, 'expense', 'manutencao', 
                'Manutenção', new DateTime('2024-01-20'), 'completed'
            ),
            new FinancialTransaction(
                null, 2, 800.00, 'revenue', 'taxa_condominio', 
                'Taxa Cond', new DateTime('2024-01-25'), 'pending'
            )
        ];

        // Salvar transações
        $savedTransactions = array_map(fn($t) => $this->repository->save($t), $transactions);

        // Filtros de teste
        $revenueFilters = [
            'condominium_id' => 1,
            'type' => 'revenue',
            'start_date' => new DateTime('2024-01-01'),
            'end_date' => new DateTime('2024-01-31')
        ];

        $expenseFilters = [
            'condominium_id' => 1,
            'type' => 'expense',
            'start_date' => new DateTime('2024-01-01'),
            'end_date' => new DateTime('2024-01-31')
        ];

        // Buscar transações por filtros
        $revenueResults = $this->repository->findByFilters($revenueFilters);
        $expenseResults = $this->repository->findByFilters($expenseFilters);

        // Verificações
        $this->assertCount(1, $revenueResults);
        $this->assertCount(1, $expenseResults);

        $revenueTransaction = $revenueResults[0];
        $expenseTransaction = $expenseResults[0];

        $this->assertEquals(1000.00, $revenueTransaction->getAmount());
        $this->assertEquals('aluguel', $revenueTransaction->getCategory());

        $this->assertEquals(500.00, $expenseTransaction->getAmount());
        $this->assertEquals('manutencao', $expenseTransaction->getCategory());
    }

    public function testCountByFilters(): void {
        // Criar múltiplas transações de teste
        $transactions = [
            new FinancialTransaction(
                null, 1, 1000.00, 'revenue', 'aluguel', 
                'Aluguel Jan', new DateTime('2024-01-15'), 'completed'
            ),
            new FinancialTransaction(
                null, 1, 500.00, 'expense', 'manutencao', 
                'Manutenção', new DateTime('2024-01-20'), 'completed'
            ),
            new FinancialTransaction(
                null, 1, 800.00, 'revenue', 'taxa_condominio', 
                'Taxa Cond', new DateTime('2024-01-25'), 'pending'
            )
        ];

        // Salvar transações
        array_map(fn($t) => $this->repository->save($t), $transactions);

        // Filtros de teste
        $condominiumFilters = ['condominium_id' => 1];
        $revenueFilters = [
            'condominium_id' => 1,
            'type' => 'revenue'
        ];

        // Contar transações
        $totalCount = $this->repository->countByFilters($condominiumFilters);
        $revenueCount = $this->repository->countByFilters($revenueFilters);

        // Verificações
        $this->assertEquals(3, $totalCount);
        $this->assertEquals(2, $revenueCount);
    }

    public function testCalculateTotalBalance(): void {
        // Criar múltiplas transações de teste
        $transactions = [
            new FinancialTransaction(
                null, 1, 1000.00, 'revenue', 'aluguel', 
                'Aluguel Jan', new DateTime('2024-01-15'), 'completed'
            ),
            new FinancialTransaction(
                null, 1, 500.00, 'expense', 'manutencao', 
                'Manutenção', new DateTime('2024-01-20'), 'completed'
            ),
            new FinancialTransaction(
                null, 1, 800.00, 'revenue', 'taxa_condominio', 
                'Taxa Cond', new DateTime('2024-01-25'), 'pending'
            )
        ];

        // Salvar transações
        array_map(fn($t) => $this->repository->save($t), $transactions);

        // Filtros de teste
        $filters = [
            'condominium_id' => 1,
            'start_date' => new DateTime('2024-01-01'),
            'end_date' => new DateTime('2024-01-31')
        ];

        // Calcular saldo total
        $totalBalance = $this->repository->calculateTotalBalance($filters);

        // Verificações
        $this->assertEquals(1300.00, $totalBalance);
    }
}
