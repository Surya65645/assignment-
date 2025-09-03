
# Products API - Plain PHP (single-folder project)

This is a minimal PHP project implementing three APIs for managing products, stored in a JSON file.
It does **not** require a framework and can run with PHP's built-in server.

## Endpoints
- `POST /api/products` - Create product (required: name, price, quantity)
- `GET /api/products/{id}` - Get product by id
- `PUT /api/products/{id}` - Update product (partial updates allowed)

## Run locally
From repository root:
```bash
# start PHP built-in server (document root is public/)
php -S 127.0.0.1:8000 -t public
```

Then use Postman or curl to test:
```bash
curl -X POST http://127.0.0.1:8000/api/products -H "Content-Type: application/json" -d '{"name":"Pen","price":9.5,"quantity":10}'
curl http://127.0.0.1:8000/api/products/1
curl -X PUT http://127.0.0.1:8000/api/products/1 -H "Content-Type: application/json" -d '{"price":12.0}'
```

## Storage
Products are saved in `storage/products.json` (auto-created).

## Notes
- Input and output use JSON.
- Proper HTTP status codes and validation messages are returned.
