<?php

namespace App\Tests\Functional\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class ArticleControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private string $jwtToken;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->authenticateAsEditor();
    }

    private function authenticateAsEditor(): void
    {
        $response = $this->client->request('POST', '/api/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'editor@symfony-cms.local',
            'password' => 'editor123',
        ]));

        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->jwtToken = $data['token'] ?? '';
        $this->assertNotEmpty($this->jwtToken, 'JWT token should not be empty');
    }

    public function testListArticles(): void
    {
        $this->client->request('GET', '/api/v1/articles', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->jwtToken,
        ]);

        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('meta', $data);
        $this->assertIsArray($data['data']);
    }

    public function testCreateArticle(): void
    {
        $articleData = [
            'title' => 'Test Article via API',
            'content' => 'This is test content for the article created via API',
            'author' => 'Test Author',
        ];

        $this->client->request('POST', '/api/v1/articles', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->jwtToken,
            'CONTENT_TYPE' => 'application/json',
        ], json_encode($articleData));

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Test Article via API', $data['title']);
        $this->assertEquals('This is test content for the article created via API', $data['content']);
    }

    public function testGetArticleById(): void
    {
        // First create an article
        $this->client->request('POST', '/api/v1/articles', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->jwtToken,
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'title' => 'Article to Retrieve',
            'content' => 'Content for retrieval test',
        ]));

        $createData = json_decode($this->client->getResponse()->getContent(), true);
        $articleId = $createData['id'];

        // Now retrieve it
        $this->client->request('GET', '/api/v1/articles/' . $articleId, [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->jwtToken,
        ]);

        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Article to Retrieve', $data['title']);
    }

    public function testUnauthorizedAccessWithoutToken(): void
    {
        $this->client->request('GET', '/api/v1/articles');
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }
}
