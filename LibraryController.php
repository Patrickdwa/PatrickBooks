<?php
declare(strict_types=1);

class LibraryController {
    private PDO $pdo;
    private $mongo;
    private string $csrfToken;

    public function __construct(PDO $pdo, $mongoManager) {
        $this->pdo = $pdo;
        $this->mongo = $mongoManager;
        $this->initCSRF();
    }

    private function initCSRF(): void {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        $this->csrfToken = $_SESSION['csrf_token'];
    }

    public function getCsrfToken(): string {
        return $this->csrfToken;
    }

    private function verifyCsrf(string $token): bool {
        return hash_equals($this->csrfToken, $token);
    }

    private function query(string $sql, array $params = [], bool $isSelect = false) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $isSelect ? $stmt : true;
        } catch (PDOException $e) {
            error_log("DB Error: " . $e->getMessage());
            throw new Exception("Database operation failed: " . $e->getMessage());
        }
    }

    private function logActivity(string $action, string $desc, array $details = []): void {
        if (!$this->mongo) return;
        try {
            $bulk = new MongoDB\Driver\BulkWrite;
            $doc = [
                'action' => $action,
                'description' => $desc,
                'details' => $details,
                'user_ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN',
                'timestamp' => new MongoDB\BSON\UTCDateTime(time() * 1000)
            ];
            $bulk->insert($doc);
            $this->mongo->executeBulkWrite(MONGO_DB_LOGS . '.activities', $bulk);
        } catch (Exception $e) {
            error_log("Mongo Log Error: " . $e->getMessage());
        }
    }

    public function handleRequest(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $token = $_POST['csrf_token'] ?? '';
        if (!$this->verifyCsrf($token)) {
            $_SESSION['toast'] = ['type' => 'danger', 'msg' => 'Security token mismatch.'];
            return;
        }

        try {
            if (isset($_POST['action'])) {
                switch ($_POST['action']) {
                    case 'add_book':    $this->addBook(); break;
                    case 'edit_book':   $this->editBook(); break;
                    case 'delete_book': $this->deleteBook(); break;
                    
                    case 'add_member':    $this->addMember(); break;
                    case 'edit_member':   $this->editMember(); break;
                    case 'delete_member': $this->deleteMember(); break;
                    
                    case 'add_loan':    $this->addLoan(); break;
                    case 'edit_loan':   $this->editLoan(); break;
                    case 'delete_loan': $this->deleteLoan(); break;
                }
            }
        } catch (Exception $e) {
            $_SESSION['toast'] = ['type' => 'danger', 'msg' => $e->getMessage()];
        }
        
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }

    // --- Book Methods ---
    private function addBook(): void {
        $this->query(
            "INSERT INTO books (title, author, publisher, year_published, genre) VALUES (?, ?, ?, ?, ?)",
            [trim($_POST['title']), trim($_POST['author']), trim($_POST['publisher']), (int)$_POST['year_published'], trim($_POST['genre'])]
        );
        $this->logActivity("ADD_BOOK", "Added book: " . trim($_POST['title']));
        $_SESSION['toast'] = ['type' => 'success', 'msg' => 'Book added successfully!'];
    }

    private function editBook(): void {
        $id = (int)$_POST['book_id'];
        $this->query(
            "UPDATE books SET title=?, author=?, publisher=?, year_published=?, genre=? WHERE book_id=?",
            [trim($_POST['title']), trim($_POST['author']), trim($_POST['publisher']), (int)$_POST['year_published'], trim($_POST['genre']), $id]
        );
        $this->logActivity("EDIT_BOOK", "Updated book ID: $id");
        $_SESSION['toast'] = ['type' => 'info', 'msg' => 'Book updated successfully.'];
    }

    private function deleteBook(): void {
        $id = (int)$_POST['id'];
        $check = $this->query("SELECT COUNT(*) FROM loans WHERE book_id = ? AND (return_date >= CURDATE() OR return_date IS NULL)", [$id], true);
        if ($check->fetchColumn() > 0) throw new Exception("Cannot delete: Book is currently on loan.");

        $this->query("DELETE FROM books WHERE book_id = ?", [$id]);
        $this->logActivity("DELETE_BOOK", "Deleted book ID: $id");
        $_SESSION['toast'] = ['type' => 'warning', 'msg' => 'Book deleted.'];
    }

    // --- Member Methods ---
    private function addMember(): void {
        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        if (!$email) throw new Exception("Invalid email address.");
        
        $check = $this->query("SELECT COUNT(*) FROM members WHERE email = ?", [$email], true);
        if ($check->fetchColumn() > 0) throw new Exception("Email already exists.");

        $this->query(
            "INSERT INTO members (name, email, phone, address) VALUES (?, ?, ?, ?)",
            [trim($_POST['name']), $email, trim($_POST['phone']), trim($_POST['address'])]
        );
        $this->logActivity("ADD_MEMBER", "New member: " . trim($_POST['name']));
        $_SESSION['toast'] = ['type' => 'success', 'msg' => 'Member registered!'];
    }

    private function editMember(): void {
        $id = (int)$_POST['member_id'];
        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        if (!$email) throw new Exception("Invalid email address.");

        $this->query(
            "UPDATE members SET name=?, email=?, phone=?, address=? WHERE member_id=?",
            [trim($_POST['name']), $email, trim($_POST['phone']), trim($_POST['address']), $id]
        );
        $this->logActivity("EDIT_MEMBER", "Updated member ID: $id");
        $_SESSION['toast'] = ['type' => 'info', 'msg' => 'Member details updated.'];
    }

    private function deleteMember(): void {
        $id = (int)$_POST['id'];
        $check = $this->query("SELECT COUNT(*) FROM loans WHERE member_id = ? AND return_date >= CURDATE()", [$id], true);
        if ($check->fetchColumn() > 0) throw new Exception("Member has active loans.");

        $this->query("DELETE FROM members WHERE member_id = ?", [$id]);
        $this->logActivity("DELETE_MEMBER", "Deleted member ID: $id");
        $_SESSION['toast'] = ['type' => 'warning', 'msg' => 'Member removed.'];
    }

    // --- Loan Methods ---
    private function addLoan(): void {
        $loanDate = $_POST['loan_date'];
        $returnDate = $_POST['return_date'];
        $bookId = (int)$_POST['book_id'];

        if ($returnDate <= $loanDate) throw new Exception("Return date must be after loan date.");
        
        $check = $this->query("SELECT COUNT(*) FROM loans WHERE book_id = ? AND return_date >= CURDATE()", [$bookId], true);
        if ($check->fetchColumn() > 0) throw new Exception("Book is already on loan.");

        $this->query(
            "INSERT INTO loans (book_id, member_id, loan_date, return_date) VALUES (?, ?, ?, ?)",
            [$bookId, (int)$_POST['member_id'], $loanDate, $returnDate]
        );
        $this->logActivity("LOAN_BOOK", "Loan created for Book ID: $bookId");
        $_SESSION['toast'] = ['type' => 'success', 'msg' => 'Loan recorded!'];
    }

    private function editLoan(): void {
        $id = (int)$_POST['loan_id'];
        $loanDate = $_POST['loan_date'];
        $returnDate = $_POST['return_date'];

        if ($returnDate <= $loanDate) throw new Exception("Return date must be after loan date.");

        $this->query(
            "UPDATE loans SET loan_date=?, return_date=? WHERE loan_id=?",
            [$loanDate, $returnDate, $id]
        );
        $this->logActivity("EDIT_LOAN", "Updated dates for Loan ID: $id");
        $_SESSION['toast'] = ['type' => 'info', 'msg' => 'Loan dates updated.'];
    }

    private function deleteLoan(): void {
        $id = (int)$_POST['id'];
        $this->query("DELETE FROM loans WHERE loan_id = ?", [$id]);
        $this->logActivity("DELETE_LOAN", "Deleted loan ID: $id");
        $_SESSION['toast'] = ['type' => 'warning', 'msg' => 'Loan record deleted.'];
    }

    // --- Getters ---
    public function getCounts(): array {
        return [
            'books' => $this->pdo->query("SELECT COUNT(*) FROM books")->fetchColumn(),
            'members' => $this->pdo->query("SELECT COUNT(*) FROM members")->fetchColumn(),
            'active_loans' => $this->pdo->query("SELECT COUNT(*) FROM loans WHERE return_date >= CURDATE()")->fetchColumn(),
            'logs' => $this->getMongoCount()
        ];
    }

    private function getMongoCount() {
        if (!$this->mongo) return 0;
        try {
            $cmd = new MongoDB\Driver\Command(["count" => "activities"]);
            $result = $this->mongo->executeCommand(MONGO_DB_LOGS, $cmd);
            return $result->toArray()[0]->n ?? 0;
        } catch (Exception $e) { return 0; }
    }

    public function getBooks() { return $this->pdo->query("SELECT * FROM books ORDER BY book_id DESC"); }
    public function getMembers() { return $this->pdo->query("SELECT * FROM members ORDER BY member_id DESC"); }
    public function getLoans() { 
        return $this->pdo->query("SELECT l.*, b.title, m.name 
            FROM loans l 
            JOIN books b ON l.book_id = b.book_id 
            JOIN members m ON l.member_id = m.member_id 
            ORDER BY l.loan_id DESC"); 
    }
    public function getLogs() {
        if (!$this->mongo) return [];
        $query = new MongoDB\Driver\Query([], ['sort' => ['timestamp' => -1], 'limit' => 100]);
        return $this->mongo->executeQuery(MONGO_DB_LOGS . '.activities', $query);
    }
}
?>