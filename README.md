# Symfony CMS - Hybrid Database Architecture

A modern headless CMS built with Symfony 8.0, demonstrating a hybrid database architecture using MySQL for structured content and MongoDB for flexible data.

## 🏗️ Architecture

This project showcases a production-ready architecture that combines the strengths of both SQL and NoSQL databases:

- **MySQL (Doctrine ORM)**: Manages structured data (Articles, Users) with ACID guarantees
- **MongoDB (Doctrine ODM)**: Stores tech products with flexible, dynamic specifications
- **Hybrid Relationships**: MySQL junction table stores relationships with MongoDB ObjectIds

### Key Technical Decisions

1. **Dual Database Strategy**
   - MySQL provides referential integrity and transaction support for articles
   - MongoDB enables schema-less storage for product specifications
   - Junction table bridges both databases for many-to-many relationships

2. **RESTful API Design**
   - Versioned endpoints (`/api/v1/`)
   - JWT authentication with role-based access control
   - Comprehensive error handling with field-level validation

3. **Security Architecture**
   - JWT tokens for stateless API authentication
   - Session-based authentication for admin interface
   - Role hierarchy: ADMIN > EDITOR > VIEWER
   - Voter-based authorization for fine-grained access control

## 🚀 Quick Start

### Prerequisites

- Docker & Docker Compose
- Git

### Installation

```bash
# Clone the repository
git clone <repository-url>
cd symfony-cms

# Start Docker containers
docker-compose up -d

# Install dependencies
docker-compose exec php composer install

# Generate JWT keypair
docker-compose exec php php bin/console lexik:jwt:generate-keypair

# Create databases
docker-compose exec mysql mysql -uroot -prootpassword -e "CREATE DATABASE IF NOT EXISTS symfony_cms"
docker-compose exec php php bin/console doctrine:migrations:migrate --no-interaction

# Create MongoDB indexes
docker-compose exec php php bin/console doctrine:mongodb:schema:create

# Load test data
docker-compose exec php php bin/console doctrine:fixtures:load --no-interaction
```

### Access Points

- **API Base URL**: http://localhost/api/v1
- **API Documentation**: http://localhost/api/doc
- **Admin Panel**: http://localhost/admin/login
- **Adminer (MySQL)**: http://localhost:8080

### Test Credentials

```
Admin:  admin@symfony-cms.local  / admin123
Editor: editor@symfony-cms.local / editor123
Viewer: viewer@symfony-cms.local / viewer123
```

## 📚 API Documentation

### Authentication

Obtain a JWT token:

```bash
curl -X POST http://localhost/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"editor@symfony-cms.local","password":"editor123"}'
```

Response:
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..."
}
```

Use the token in subsequent requests:
```bash
curl http://localhost/api/v1/articles \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

### Articles API

#### List Articles
```bash
GET /api/v1/articles?page=1&limit=10
```

#### Get Single Article
```bash
GET /api/v1/articles/{id}
GET /api/v1/articles/slug/{slug}
```

#### Create Article (ROLE_EDITOR+)
```bash
POST /api/v1/articles
Content-Type: application/json
Authorization: Bearer {token}

{
  "title": "My Article",
  "content": "Article content here...",
  "slug": "my-article",
  "author": "John Doe",
  "publishDate": "2024-02-19T10:00:00Z"
}
```

#### Update Article (ROLE_EDITOR+)
```bash
PUT /api/v1/articles/{id}
Content-Type: application/json
Authorization: Bearer {token}

{
  "title": "Updated Title",
  "content": "Updated content..."
}
```

#### Delete Article (ROLE_ADMIN)
```bash
DELETE /api/v1/articles/{id}
Authorization: Bearer {token}
```

### TechProducts API

#### List Tech Products
```bash
GET /api/v1/techproducts?page=1&limit=10
```

#### Search Tech Products
```bash
GET /api/v1/techproducts/search?q=laptop&limit=20
```

#### Create Tech Product (ROLE_EDITOR+)
```bash
POST /api/v1/techproducts
Content-Type: application/json
Authorization: Bearer {token}

{
  "productId": "TP-2024-001",
  "name": "Gaming Laptop",
  "specs": {
    "processor": "Intel i7-12700K",
    "memory": "32GB DDR5",
    "storage": "1TB NVMe SSD",
    "graphics": "NVIDIA RTX 4070"
  },
  "pricing": {
    "currency": "USD",
    "amount": 1599.99,
    "discount": 10
  }
}
```

### Hybrid Queries (Cross-Database)

#### Get Article with Associated Products
```bash
GET /api/v1/articles/{id}/with-products
Authorization: Bearer {token}
```

Response:
```json
{
  "article": {
    "id": 1,
    "title": "Best Gaming Laptops 2024",
    "slug": "best-gaming-laptops-2024",
    "content": "...",
    "publishDate": "2024-02-19T10:00:00+00:00"
  },
  "techProducts": [
    {
      "id": "65d3f8a1b2c3d4e5f6g7h8i9",
      "productId": "TP-2024-001",
      "name": "Gaming Laptop",
      "specs": {
        "processor": "Intel i7-12700K",
        "memory": "32GB DDR5"
      }
    }
  ]
}
```

#### Associate Product with Article (ROLE_EDITOR+)
```bash
POST /api/v1/articles/{articleId}/techproducts
Content-Type: application/json
Authorization: Bearer {token}

{
  "techProductId": "65d3f8a1b2c3d4e5f6g7h8i9",
  "sortOrder": 0
}
```

#### Remove Association (ROLE_EDITOR+)
```bash
DELETE /api/v1/articles/{articleId}/techproducts/{techProductId}
Authorization: Bearer {token}
```

## 🔐 Authorization

### Role Hierarchy

```
ROLE_ADMIN
  ├── Can delete any content
  ├── Can manage users
  └── Inherits ROLE_EDITOR

ROLE_EDITOR
  ├── Can create/update articles and products
  ├── Can associate products with articles
  └── Inherits ROLE_VIEWER

ROLE_VIEWER
  └── Read-only access to all content
```

### Voter-Based Authorization

Fine-grained permissions are enforced through Symfony Voters:

- **ArticleVoter**: Controls access to article operations
  - VIEW: Authenticated users can view published articles; editors can view drafts
  - EDIT: Admins, editors, or article owners
  - DELETE: Admins only

- **TechProductVoter**: Controls access to tech product operations
  - VIEW: All authenticated users
  - EDIT: Editors and admins
  - DELETE: Admins only

## 🎨 Admin Interface

Access the admin panel at http://localhost/admin/login

### Features

- **Dashboard**: Statistics and quick actions
- **Article Management**: Create, edit, delete articles with rich text content
- **Tech Product Management**: Manage products with JSON editor for flexible specs
- **User Management** (ROLE_ADMIN): Create users and assign roles
- **Product Association**: Link tech products to articles with drag-and-drop interface

## 🗄️ Database Schema

### MySQL (Doctrine ORM)

**users**
```sql
id, email (unique), password, roles (JSON), created_at, updated_at
```

**articles**
```sql
id, title, slug (unique, indexed), content, author, 
publish_date, created_at, updated_at, user_id (FK)
```

**article_techproduct** (Junction Table)
```sql
id, article_id (FK), techproduct_mongo_id (string, 24 chars),
sort_order, created_at
UNIQUE (article_id, techproduct_mongo_id)
```

### MongoDB (Doctrine ODM)

**tech_products**
```json
{
  "_id": ObjectId,
  "product_id": "TP-2024-001",  // unique, indexed
  "name": "Product Name",
  "specs": {
    // Completely flexible - any structure
    "processor": "Intel i7",
    "memory": "16GB"
  },
  "pricing": {
    "currency": "USD",
    "amount": 999.99
  },
  "created_at": ISODate,
  "updated_at": ISODate
}
```

## 🧪 Testing

### Run Unit Tests

```bash
docker-compose exec php php bin/phpunit tests/Unit
```

### Test Coverage

- ArticleService: CRUD operations, slug generation
- TechProductService: MongoDB operations, flexible specs
- ArticleTechProductService: Hybrid database queries
- Voters: Authorization logic

## 📊 Logging

Structured logging with dedicated channels:

```bash
# View API request/response logs
docker-compose exec php tail -f var/log/api.log

# View admin activity logs
docker-compose exec php tail -f var/log/admin.log

# View security events
docker-compose exec php tail -f var/log/security.log
```

Log format includes:
- Request method, URI, IP address
- Response status code
- Request duration in milliseconds
- Unique request ID for tracing

## 🛠️ Development

### Project Structure

```
symfony-cms/
├── config/
│   ├── packages/         # Bundle configurations
│   └── routes/           # Route definitions
├── docker/
│   ├── nginx/            # Nginx configuration
│   └── php/              # PHP-FPM Dockerfile
├── src/
│   ├── Controller/
│   │   ├── Api/V1/       # API controllers
│   │   └── Admin/        # Admin controllers
│   ├── Entity/           # MySQL entities
│   ├── Document/         # MongoDB documents
│   ├── Repository/       # Data access layer
│   ├── Service/          # Business logic
│   ├── DTO/              # Data Transfer Objects
│   ├── Security/Voter/   # Authorization voters
│   └── EventSubscriber/  # Event listeners
├── templates/            # Twig templates
├── tests/
│   ├── Unit/             # Unit tests
│   └── Functional/       # API tests
└── docker-compose.yml    # Docker configuration
```

### Common Commands

```bash
# Clear cache
docker-compose exec php php bin/console cache:clear

# Create migration
docker-compose exec php php bin/console make:migration

# Run migrations
docker-compose exec php php bin/console doctrine:migrations:migrate

# Update MongoDB schema
docker-compose exec php php bin/console doctrine:mongodb:schema:update

# Generate new entity
docker-compose exec php php bin/console make:entity

# View routes
docker-compose exec php php bin/console debug:router

# Check services
docker-compose exec php php bin/console debug:container
```

## 🚢 Deployment Considerations

### Production Checklist

- [ ] Set `APP_ENV=prod` in `.env`
- [ ] Generate strong `APP_SECRET`
- [ ] Configure real database credentials
- [ ] Set up CORS for your frontend domain
- [ ] Enable opcache and APCu
- [ ] Configure Redis for session storage
- [ ] Set up SSL/TLS certificates
- [ ] Configure rate limiting
- [ ] Set up monitoring (e.g., Sentry)
- [ ] Configure log rotation
- [ ] Set up database backups

### Environment Variables

Key environment variables in `.env`:

```bash
APP_ENV=dev
APP_SECRET=your-secret-here
DATABASE_URL=mysql://user:pass@host:3306/db_name
MONGODB_URI=mongodb://user:pass@host:27017/?authSource=admin
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=your-passphrase
```

## 📖 Further Reading

- [Symfony Documentation](https://symfony.com/doc/current/index.html)
- [Doctrine ORM](https://www.doctrine-project.org/projects/doctrine-orm/en/latest/)
- [Doctrine MongoDB ODM](https://www.doctrine-project.org/projects/doctrine-mongodb-odm/en/latest/)
- [LexikJWTAuthenticationBundle](https://github.com/lexik/LexikJWTAuthenticationBundle)

## 📄 License

This project is open-source software licensed under the MIT license.

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

---

Built with ❤️ using Symfony 8.0, PHP 8.4, MySQL 8.0, and MongoDB 8.0
