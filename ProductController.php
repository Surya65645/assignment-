<?php

namespace App;

<?php
namespace App;

class ProductController
{
    private string $filePath;

    public function __construct(string $filePath)
    {
        $this->filePath = $filePath;
        if (!file_exists($this->filePath)) {
            file_put_contents($this->filePath, json_encode([]));
        }
    }

    /** Load products from JSON file */
    private function loadProducts(): array
    {
        $json = file_get_contents($this->filePath);
        $data = json_decode($json, true);
        return is_array($data) ? $data : [];
    }

    /** Save products to JSON file */
    private function saveProducts(array $products): bool
    {
        return file_put_contents($this->filePath, json_encode($products, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) !== false;
    }

    /** Generate next ID */
    private function nextId(array $products): int
    {
        $max = 0;
        foreach ($products as $p) {
            if (isset($p['id']) && is_int($p['id'])) {
                $max = max($max, $p['id']);
            }
        }
        return $max + 1;
    }

    /** Validate input for creating a product */
    private function validateCreate(array $data): array
    {
        $errors = [];

        if (!isset($data['name']) || trim($data['name']) === '') {
            $errors['name'][] = 'The name field is required.';
        } else {
            if (!is_string($data['name'])) {
                $errors['name'][] = 'The name must be a string.';
            } elseif (mb_strlen($data['name']) > 255) {
                $errors['name'][] = 'The name may not be greater than 255 characters.';
            }
        }

        if (isset($data['description']) && !is_string($data['description']) && !is_null($data['description'])) {
            $errors['description'][] = 'The description must be a string or null.';
        }

        if (!isset($data['price'])) {
            $errors['price'][] = 'The price field is required.';
        } else {
            if (!is_numeric($data['price'])) {
                $errors['price'][] = 'The price must be a number.';
            } elseif ((float)$data['price'] <= 0) {
                $errors['price'][] = 'The price must be greater than 0.';
            }
        }

        if (!isset($data['quantity'])) {
            $errors['quantity'][] = 'The quantity field is required.';
        } else {
            // allow numeric strings that are integers
            if (!is_int($data['quantity']) && !(is_string($data['quantity']) && ctype_digit($data['quantity']))) {
                $errors['quantity'][] = 'The quantity must be an integer.';
            } elseif ((int)$data['quantity'] < 0) {
                $errors['quantity'][] = 'The quantity must be at least 0.';
            }
        }

        return $errors;
    }

    /** Validate partial update fields */
    private function validateUpdate(array $data): array
    {
        $errors = [];

        if (isset($data['name'])) {
            if (!is_string($data['name'])) {
                $errors['name'][] = 'The name must be a string.';
            } elseif (mb_strlen($data['name']) > 255) {
                $errors['name'][] = 'The name may not be greater than 255 characters.';
            }
        }

        if (array_key_exists('description', $data) && !is_string($data['description']) && !is_null($data['description'])) {
            $errors['description'][] = 'The description must be a string or null.';
        }

        if (isset($data['price'])) {
            if (!is_numeric($data['price'])) {
                $errors['price'][] = 'The price must be a number.';
            } elseif ((float)$data['price'] <= 0) {
                $errors['price'][] = 'The price must be greater than 0.';
            }
        }

        if (isset($data['quantity'])) {
            if (!is_int($data['quantity']) && !(is_string($data['quantity']) && ctype_digit($data['quantity']))) {
                $errors['quantity'][] = 'The quantity must be an integer.';
            } elseif ((int)$data['quantity'] < 0) {
                $errors['quantity'][] = 'The quantity must be at least 0.';
            }
        }

        return $errors;
    }

    /** Send JSON response helper */
    private function jsonResponse($payload, int $status = 200): void
    {
        http_response_code($status);
        echo json_encode($payload);
    }

    /** Create product */
    public function store(?array $input): void
    {
        $input = is_array($input) ? $input : [];

        $errors = $this->validateCreate($input);
        if (!empty($errors)) {
            $this->jsonResponse([
                'message' => 'Validation failed.',
                'errors' => $errors
            ], 422);
            return;
        }

        $products = $this->loadProducts();

        $product = [
            'id' => $this->nextId($products),
            'name' => $input['name'],
            'description' => $input['description'] ?? null,
            'price' => (float)$input['price'],
            'quantity' => (int)$input['quantity'],
            'created_at' => date(DATE_ATOM),
            'updated_at' => date(DATE_ATOM)
        ];

        $products[] = $product;
        if (!$this->saveProducts($products)) {
            $this->jsonResponse(['message' => 'Failed to save product.'], 500);
            return;
        }

        $this->jsonResponse($product, 201);
    }

    /** Get product by id */
    public function show(int $id): void
    {
        $products = $this->loadProducts();
        foreach ($products as $p) {
            if ($p['id'] === $id) {
                $this->jsonResponse($p, 200);
                return;
            }
        }
        $this->jsonResponse(['message' => 'Product not found.'], 404);
    }

    /** Update product partially */
    public function update(int $id, ?array $input): void
    {
        $input = is_array($input) ? $input : [];

        if (empty($input)) {
            $this->jsonResponse(['message' => 'No data provided for update.'], 400);
            return;
        }

        $errors = $this->validateUpdate($input);
        if (!empty($errors)) {
            $this->jsonResponse(['message' => 'Validation failed.', 'errors' => $errors], 422);
            return;
        }

        $products = $this->loadProducts();
        $found = false;
        foreach ($products as &$p) {
            if ($p['id'] === $id) {
                $found = true;
                if (isset($input['name'])) $p['name'] = $input['name'];
                if (array_key_exists('description', $input)) $p['description'] = $input['description'];
                if (isset($input['price'])) $p['price'] = (float)$input['price'];
                if (isset($input['quantity'])) $p['quantity'] = (int)$input['quantity'];
                $p['updated_at'] = date(DATE_ATOM);
                break;
            }
        }
        unset($p);

        if (!$found) {
            $this->jsonResponse(['message' => 'Product not found.'], 404);
            return;
        }

        if (!$this->saveProducts($products)) {
            $this->jsonResponse(['message' => 'Failed to save product.'], 500);
            return;
        }

        // return updated product
        foreach ($products as $p) {
            if ($p['id'] === $id) {
                $this->jsonResponse($p, 200);
                return;
            }
        }
    }
}
