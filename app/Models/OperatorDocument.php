<?php
namespace App\Models;

/**
 * OperatorDocument — Documents shared by operators with agents
 *
 * Visibility:
 *   - 'all_agents'    → visible to every approved agent of this operator
 *   - 'contract'      → visible only to agents on a specific contract
 *   - 'operator_only' → operator's internal use only (not shown in agent portal)
 */
class OperatorDocument extends BaseModel
{
    protected string $table = 'tour_operator_documents';
    protected bool $useCompanyFilter = false;

    /**
     * List documents for an operator (operator-side admin view)
     */
    public function listForOperator(int $operatorComId): array
    {
        $operatorComId = intval($operatorComId);
        $sql = "SELECT d.*,
                       ac.contract_name,
                       u.email AS uploader_email
                FROM tour_operator_documents d
                LEFT JOIN agent_contracts ac ON d.contract_id = ac.id
                LEFT JOIN authorize u ON d.uploaded_by = u.id
                WHERE d.operator_company_id = $operatorComId
                  AND d.deleted_at IS NULL
                ORDER BY d.created_at DESC";
        $res = mysqli_query($this->conn, $sql);
        return $res ? mysqli_fetch_all($res, MYSQLI_ASSOC) : [];
    }

    /**
     * List documents visible to a specific agent
     */
    public function listForAgent(int $agentComId): array
    {
        $agentComId = intval($agentComId);
        // Documents are visible if:
        //   - visibility='all_agents' AND agent is approved for the operator, OR
        //   - visibility='contract' AND agent is assigned to that contract
        $sql = "SELECT DISTINCT d.*,
                       ac.contract_name,
                       co.name_en AS operator_name
                FROM tour_operator_documents d
                LEFT JOIN agent_contracts ac ON d.contract_id = ac.id
                LEFT JOIN company co ON d.operator_company_id = co.id
                LEFT JOIN tour_operator_agents oa
                    ON oa.operator_company_id = d.operator_company_id
                    AND oa.agent_company_id = $agentComId
                    AND oa.status = 'approved'
                    AND oa.deleted_at IS NULL
                LEFT JOIN tour_contract_agents ca
                    ON ca.contract_id = d.contract_id
                    AND ca.agent_company_id = $agentComId
                WHERE d.deleted_at IS NULL
                  AND (
                       (d.visibility = 'all_agents' AND oa.id IS NOT NULL)
                    OR (d.visibility = 'contract' AND ca.id IS NOT NULL)
                  )
                ORDER BY co.name_en, d.category, d.created_at DESC";
        $res = mysqli_query($this->conn, $sql);
        return $res ? mysqli_fetch_all($res, MYSQLI_ASSOC) : [];
    }

    /**
     * Get a single document with operator scope check
     */
    public function getDocument(int $id, int $operatorComId): ?array
    {
        $id = intval($id);
        $operatorComId = intval($operatorComId);
        $sql = "SELECT * FROM tour_operator_documents
                WHERE id = $id AND operator_company_id = $operatorComId
                  AND deleted_at IS NULL
                LIMIT 1";
        $res = mysqli_query($this->conn, $sql);
        $row = $res ? mysqli_fetch_assoc($res) : null;
        return $row ?: null;
    }

    /**
     * Check if an agent can access a document
     */
    public function canAgentAccess(int $documentId, int $agentComId): ?array
    {
        $documentId = intval($documentId);
        $agentComId = intval($agentComId);
        $sql = "SELECT d.*
                FROM tour_operator_documents d
                LEFT JOIN tour_operator_agents oa
                    ON oa.operator_company_id = d.operator_company_id
                    AND oa.agent_company_id = $agentComId
                    AND oa.status = 'approved'
                LEFT JOIN tour_contract_agents ca
                    ON ca.contract_id = d.contract_id
                    AND ca.agent_company_id = $agentComId
                WHERE d.id = $documentId
                  AND d.deleted_at IS NULL
                  AND (
                       (d.visibility = 'all_agents' AND oa.id IS NOT NULL)
                    OR (d.visibility = 'contract' AND ca.id IS NOT NULL)
                  )
                LIMIT 1";
        $res = mysqli_query($this->conn, $sql);
        $row = $res ? mysqli_fetch_assoc($res) : null;
        return $row ?: null;
    }

    /**
     * Create a document record after file upload
     */
    public function createDocument(array $data): int
    {
        $operatorComId = intval($data['operator_company_id'] ?? 0);
        $contractId = !empty($data['contract_id']) ? intval($data['contract_id']) : 'NULL';
        $title = mysqli_real_escape_string($this->conn, $data['title'] ?? '');
        $description = !empty($data['description']) ? "'" . mysqli_real_escape_string($this->conn, $data['description']) . "'" : 'NULL';
        $fileName = mysqli_real_escape_string($this->conn, $data['file_name'] ?? '');
        $filePath = mysqli_real_escape_string($this->conn, $data['file_path'] ?? '');
        $fileSize = intval($data['file_size'] ?? 0);
        $mimeType = !empty($data['mime_type']) ? "'" . mysqli_real_escape_string($this->conn, $data['mime_type']) . "'" : 'NULL';
        $category = mysqli_real_escape_string($this->conn, $data['category'] ?? 'other');
        $visibility = mysqli_real_escape_string($this->conn, $data['visibility'] ?? 'all_agents');
        $uploadedBy = !empty($data['uploaded_by']) ? intval($data['uploaded_by']) : 'NULL';

        if (!$operatorComId || !$title || !$fileName) return 0;

        $sql = "INSERT INTO tour_operator_documents
                    (operator_company_id, contract_id, title, description, file_name, file_path,
                     file_size, mime_type, category, visibility, uploaded_by)
                VALUES ($operatorComId, $contractId, '$title', $description, '$fileName', '$filePath',
                        $fileSize, $mimeType, '$category', '$visibility', $uploadedBy)";
        mysqli_query($this->conn, $sql);
        return (int)mysqli_insert_id($this->conn);
    }

    /**
     * Soft-delete a document
     */
    public function softDeleteDocument(int $id, int $operatorComId): bool
    {
        $id = intval($id);
        $operatorComId = intval($operatorComId);
        $sql = "UPDATE tour_operator_documents
                SET deleted_at = NOW()
                WHERE id = $id AND operator_company_id = $operatorComId";
        return (bool)mysqli_query($this->conn, $sql);
    }

    /**
     * Increment download counter
     */
    public function incrementDownload(int $id): void
    {
        $id = intval($id);
        mysqli_query($this->conn,
            "UPDATE tour_operator_documents SET download_count = download_count + 1 WHERE id = $id");
    }
}
