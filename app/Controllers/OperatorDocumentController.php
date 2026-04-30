<?php
namespace App\Controllers;

use App\Models\OperatorDocument;
use App\Models\AgentContract;

/**
 * OperatorDocumentController — Document sharing operator ↔ agent
 *
 * Routes (operator side):
 *   tour_doc_list   → list()    — Operator's document library
 *   tour_doc_upload → upload()  — POST: upload a new file
 *   tour_doc_delete → delete()  — POST: soft-delete
 *
 * Routes (agent side):
 *   agent_portal_documents → agentList()     — Documents visible to this agent
 *   agent_portal_doc_download → agentDownload() — Download a single document
 *
 * Files are stored under uploads/operator-documents/{operator_id}/
 * Allowed types: PDF, images, Word/Excel docs (whitelist enforced)
 */
class OperatorDocumentController extends BaseController
{
    private OperatorDocument $model;
    private AgentContract $contractModel;

    /** Allowed mime types (whitelist) */
    private const ALLOWED_MIME = [
        'application/pdf',
        'image/jpeg', 'image/png', 'image/gif', 'image/webp',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'text/plain',
    ];

    /** Max upload size — 10MB */
    private const MAX_SIZE = 10 * 1024 * 1024;

    public function __construct()
    {
        parent::__construct();
        $this->model = new OperatorDocument();
        $this->contractModel = new AgentContract();
    }

    private function guardModule(): bool
    {
        if (!isModuleEnabled($this->getCompanyId(), 'tour_operator')) {
            $this->redirect('dashboard');
            return false;
        }
        return true;
    }

    // ─── OPERATOR SIDE ────────────────────────────────────────

    public function list(): void
    {
        if (!$this->guardModule()) return;

        $comId = $this->getCompanyId();
        $documents = $this->model->listForOperator($comId);
        $contracts = $this->contractModel->getOperatorContracts($comId);
        $message = $this->inputStr('msg', '');

        include __DIR__ . '/../Views/tour-agent/document-list.php';
    }

    public function upload(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('tour_doc_list');
            return;
        }
        $this->verifyCsrf();
        if (!$this->guardModule()) return;

        $comId = $this->getCompanyId();
        $userId = intval($_SESSION['user_id'] ?? 0) ?: null;

        $title = $this->inputStr('title');
        $description = $this->inputStr('description');
        $category = $this->inputStr('category', 'other');
        $visibility = $this->inputStr('visibility', 'all_agents');
        $contractId = $this->inputInt('contract_id') ?: null;

        if (!$title || empty($_FILES['document_file']['name'])) {
            $this->redirect('tour_doc_list', ['msg' => 'missing_field']);
            return;
        }

        $file = $_FILES['document_file'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $this->redirect('tour_doc_list', ['msg' => 'upload_error']);
            return;
        }
        if ($file['size'] > self::MAX_SIZE) {
            $this->redirect('tour_doc_list', ['msg' => 'too_large']);
            return;
        }

        // Validate mime type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        if (!in_array($mime, self::ALLOWED_MIME, true)) {
            $this->redirect('tour_doc_list', ['msg' => 'bad_type']);
            return;
        }

        // Build storage path
        $uploadDir = __DIR__ . '/../../uploads/operator-documents/' . $comId;
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Sanitize filename, prefix with timestamp to avoid collisions
        $origName = preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($file['name']));
        $storedName = time() . '_' . bin2hex(random_bytes(4)) . '_' . $origName;
        $destPath = $uploadDir . '/' . $storedName;

        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            $this->redirect('tour_doc_list', ['msg' => 'save_failed']);
            return;
        }

        // Relative path for storage in DB (so we can rebuild the URL)
        $relativePath = 'uploads/operator-documents/' . $comId . '/' . $storedName;

        $id = $this->model->createDocument([
            'operator_company_id' => $comId,
            'contract_id'         => ($visibility === 'contract' ? $contractId : null),
            'title'               => $title,
            'description'         => $description,
            'file_name'           => $origName,
            'file_path'           => $relativePath,
            'file_size'           => $file['size'],
            'mime_type'           => $mime,
            'category'            => $category,
            'visibility'          => $visibility,
            'uploaded_by'         => $userId,
        ]);

        $this->redirect('tour_doc_list', ['msg' => $id ? 'uploaded' : 'error']);
    }

    public function delete(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('tour_doc_list');
            return;
        }
        $this->verifyCsrf();
        if (!$this->guardModule()) return;

        $comId = $this->getCompanyId();
        $id = $this->inputInt('id');

        $doc = $this->model->getDocument($id, $comId);
        if (!$doc) {
            $this->redirect('tour_doc_list', ['msg' => 'not_found']);
            return;
        }

        $this->model->softDeleteDocument($id, $comId);
        $this->redirect('tour_doc_list', ['msg' => 'deleted']);
    }

    // ─── AGENT SIDE ───────────────────────────────────────────

    public function agentList(): void
    {
        $agentComId = $this->getCompanyId();
        $documents = $this->model->listForAgent($agentComId);
        $message = $this->inputStr('msg', '');

        include __DIR__ . '/../Views/agent-portal/documents.php';
    }

    public function agentDownload(): void
    {
        $agentComId = $this->getCompanyId();
        $id = $this->inputInt('id');

        $doc = $this->model->canAgentAccess($id, $agentComId);
        if (!$doc) {
            $this->redirect('agent_portal_documents', ['msg' => 'not_found']);
            return;
        }

        $absPath = __DIR__ . '/../../' . $doc['file_path'];
        if (!file_exists($absPath)) {
            $this->redirect('agent_portal_documents', ['msg' => 'file_missing']);
            return;
        }

        $this->model->incrementDownload($id);

        // Stream file to browser
        header('Content-Type: ' . ($doc['mime_type'] ?: 'application/octet-stream'));
        header('Content-Disposition: attachment; filename="' . $doc['file_name'] . '"');
        header('Content-Length: ' . filesize($absPath));
        header('Cache-Control: private, no-cache');
        readfile($absPath);
        exit;
    }
}
