<?php

declare(strict_types=1);

/**
 * Интерфейс хранения транзакций
 */
interface TransactionStorageInterface
{
    public function addTransaction(Transaction $transaction): void;

    public function removeTransactionById(int $id): void;

    public function getAllTransactions(): array;

    public function findById(int $id): ?Transaction;
}

/**
 * Класс банковской транзакции
 */
class Transaction
{
    private int $id;
    private DateTime $date;
    private float $amount;
    private string $description;
    private string $merchant;

    public function __construct(
        int $id,
        string $date,
        float $amount,
        string $description,
        string $merchant
    ) {
        $this->id = $id;
        $this->date = new DateTime($date);
        $this->amount = $amount;
        $this->description = $description;
        $this->merchant = $merchant;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getDate(): DateTime
    {
        return $this->date;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getMerchant(): string
    {
        return $this->merchant;
    }

    /**
     * Количество дней с момента транзакции
     */
    public function getDaysSinceTransaction(): int
    {
        $currentDate = new DateTime();
        $difference = $this->date->diff($currentDate);

        return $difference->days;
    }
}

/**
 * Репозиторий транзакций
 */
class TransactionRepository implements TransactionStorageInterface
{
    /**
     * @var Transaction[]
     */
    private array $transactions = [];

    public function addTransaction(Transaction $transaction): void
    {
        $this->transactions[] = $transaction;
    }

    public function removeTransactionById(int $id): void
    {
        foreach ($this->transactions as $key => $transaction) {
            if ($transaction->getId() === $id) {
                unset($this->transactions[$key]);
            }
        }
    }

    public function getAllTransactions(): array
    {
        return $this->transactions;
    }

    public function findById(int $id): ?Transaction
    {
        foreach ($this->transactions as $transaction) {
            if ($transaction->getId() === $id) {
                return $transaction;
            }
        }

        return null;
    }
}

/**
 * Менеджер бизнес-логики
 */
class TransactionManager
{
    public function __construct(
        private TransactionStorageInterface $repository
    ) {
    }

    /**
     * Общая сумма всех транзакций
     */
    public function calculateTotalAmount(): float
    {
        $total = 0;

        foreach ($this->repository->getAllTransactions() as $transaction) {
            $total += $transaction->getAmount();
        }

        return $total;
    }

    /**
     * Сумма транзакций за период
     */
    public function calculateTotalAmountByDateRange(
        string $startDate,
        string $endDate
    ): float {
        $start = new DateTime($startDate);
        $end = new DateTime($endDate);

        $total = 0;

        foreach ($this->repository->getAllTransactions() as $transaction) {
            if (
                $transaction->getDate() >= $start &&
                $transaction->getDate() <= $end
            ) {
                $total += $transaction->getAmount();
            }
        }

        return $total;
    }

    /**
     * Количество транзакций по получателю
     */
    public function countTransactionsByMerchant(string $merchant): int
    {
        $count = 0;

        foreach ($this->repository->getAllTransactions() as $transaction) {
            if ($transaction->getMerchant() === $merchant) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Сортировка по дате
     */
    public function sortTransactionsByDate(): array
    {
        $transactions = $this->repository->getAllTransactions();

        usort($transactions, function ($a, $b) {
            return $a->getDate() <=> $b->getDate();
        });

        return $transactions;
    }

    /**
     * Сортировка по сумме
     */
    public function sortTransactionsByAmountDesc(): array
    {
        $transactions = $this->repository->getAllTransactions();

        usort($transactions, function ($a, $b) {
            return $b->getAmount() <=> $a->getAmount();
        });

        return $transactions;
    }
}

/**
 * Класс вывода таблицы
 */
final class TransactionTableRenderer
{
    public function render(array $transactions): string
    {
        $html = "
        <table border='1' cellpadding='10' cellspacing='0'>
            <tr>
                <th>ID</th>
                <th>Дата</th>
                <th>Сумма</th>
                <th>Описание</th>
                <th>Получатель</th>
                <th>Категория</th>
                <th>Дней назад</th>
            </tr>
        ";

        foreach ($transactions as $transaction) {

            $category = $this->getCategory($transaction->getMerchant());

            $html .= "
            <tr>
                <td>{$transaction->getId()}</td>
                <td>{$transaction->getDate()->format('Y-m-d')}</td>
                <td>{$transaction->getAmount()}</td>
                <td>{$transaction->getDescription()}</td>
                <td>{$transaction->getMerchant()}</td>
                <td>{$category}</td>
                <td>{$transaction->getDaysSinceTransaction()}</td>
            </tr>
            ";
        }

        $html .= "</table>";

        return $html;
    }

    private function getCategory(string $merchant): string
    {
        return match ($merchant) {
            'Steam' => 'Игры',
            'Apple' => 'Техника',
            'Amazon' => 'Покупки',
            'Netflix' => 'Подписка',
            default => 'Другое',
        };
    }
}

/*
|--------------------------------------------------------------------------
| СОЗДАНИЕ ДАННЫХ
|--------------------------------------------------------------------------
*/

$repository = new TransactionRepository();

$transactions = [
    new Transaction(1, '2025-01-10', 120.5, 'Покупка игры', 'Steam'),
    new Transaction(2, '2025-01-15', 300.0, 'Наушники', 'Apple'),
    new Transaction(3, '2025-02-01', 50.0, 'Фильм', 'Netflix'),
    new Transaction(4, '2025-02-10', 700.0, 'Монитор', 'Amazon'),
    new Transaction(5, '2025-02-18', 25.5, 'Подписка', 'Netflix'),
    new Transaction(6, '2025-03-02', 90.0, 'Клавиатура', 'Amazon'),
    new Transaction(7, '2025-03-10', 500.0, 'Телефон', 'Apple'),
    new Transaction(8, '2025-03-20', 60.0, 'Игра', 'Steam'),
    new Transaction(9, '2025-04-01', 15.0, 'Сериал', 'Netflix'),
    new Transaction(10, '2025-04-15', 1000.0, 'Ноутбук', 'Amazon'),
];

foreach ($transactions as $transaction) {
    $repository->addTransaction($transaction);
}

$manager = new TransactionManager($repository);

$renderer = new TransactionTableRenderer();

echo "<h1>Банковские транзакции</h1>";

echo $renderer->render(
    $manager->sortTransactionsByAmountDesc()
);

echo "<br><br>";

echo "<strong>Общая сумма:</strong> ";
echo $manager->calculateTotalAmount();

echo "<br><br>";

echo "<strong>Количество транзакций Amazon:</strong> ";
echo $manager->countTransactionsByMerchant('Amazon');
