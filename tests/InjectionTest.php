<?php

use PHPUnit\Framework\TestCase;

class SqlInjectionTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        // Use an isolated test database/connection rather than production data
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Replace this with your test schema / tables from database/CCAPDEV-N24-6-AnimoHub.sql
        $this->pdo->exec('
            CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                email TEXT,
                password TEXT,
                role TEXT
            )
        ');

        $stmt = $this->pdo->prepare(
            'INSERT INTO users (email, password, role) VALUES (:email, :password, :role)'
        );
        $stmt->execute([
            ':email' => 'student@dlsu.edu.ph',
            ':password' => 'hashed_or_test_secret',
            ':role' => 'student',
        ]);
    }

    public function testPreparedStatementBlocksAuthBypassPayload(): void
    {
        $payload = "' OR '1'='1";

        $stmt = $this->pdo->prepare(
            'SELECT id, email, role FROM users WHERE email = :email'
        );
        $stmt->execute([':email' => $payload]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->assertSame([], $rows);
    }

    public function testPreparedStatementBlocksCommentTruncationPayload(): void
    {
        $payload = "student@dlsu.edu.ph' --";

        $stmt = $this->pdo->prepare(
            'SELECT id, email, role FROM users WHERE email = :email AND password = :password'
        );
        $stmt->execute([
            ':email' => $payload,
            ':password' => 'anything',
        ]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->assertSame([], $rows);
    }
}
