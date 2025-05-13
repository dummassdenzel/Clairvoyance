const request = require('supertest');
const app = require('../api/index'); // Assuming this is the entry point for the API

describe('API Endpoints', () => {
  // KPI Tests
  it('should retrieve all KPIs', async () => {
    const res = await request(app).get('/api/kpis');
    expect(res.statusCode).toEqual(200);
    expect(res.body).toHaveProperty('data');
  });

  it('should create a new KPI', async () => {
    const res = await request(app)
      .post('/api/kpis')
      .send({ name: 'Test KPI', value: 100 });
    expect(res.statusCode).toEqual(200);
    expect(res.body).toHaveProperty('remarks', 'success');
  });

  it('should update an existing KPI', async () => {
    const res = await request(app)
      .put('/api/kpis/1')
      .send({ name: 'Updated KPI', value: 200 });
    expect(res.statusCode).toEqual(200);
    expect(res.body).toHaveProperty('remarks', 'success');
  });

  it('should delete a KPI', async () => {
    const res = await request(app).delete('/api/kpis/1');
    expect(res.statusCode).toEqual(200);
    expect(res.body).toHaveProperty('remarks', 'success');
  });

  // Category Tests
  it('should retrieve all categories', async () => {
    const res = await request(app).get('/api/categories');
    expect(res.statusCode).toEqual(200);
    expect(res.body).toHaveProperty('data');
  });

  it('should create a new category', async () => {
    const res = await request(app)
      .post('/api/categories')
      .send({ name: 'Test Category' });
    expect(res.statusCode).toEqual(200);
    expect(res.body).toHaveProperty('remarks', 'success');
  });

  it('should delete a category', async () => {
    const res = await request(app).delete('/api/categories/1');
    expect(res.statusCode).toEqual(200);
    expect(res.body).toHaveProperty('remarks', 'success');
  });

  // User Authentication Tests
  it('should register a new user', async () => {
    const res = await request(app)
      .post('/api/register')
      .send({ username: 'testuser', email: 'test@example.com', password: 'password123' });
    expect(res.statusCode).toEqual(200);
    expect(res.body).toHaveProperty('remarks', 'success');
  });

  it('should log in a user', async () => {
    const res = await request(app)
      .post('/api/login')
      .send({ email: 'test@example.com', password: 'password123' });
    expect(res.statusCode).toEqual(200);
    expect(res.body).toHaveProperty('remarks', 'success');
    expect(res.body).toHaveProperty('payload.token');
  });

  // File Handling Tests
  it('should download KPIs as CSV', async () => {
    const res = await request(app).get('/api/kpis/download/csv');
    expect(res.statusCode).toEqual(200);
    expect(res.headers['content-type']).toContain('text/csv');
  });

  it('should download KPIs as Excel', async () => {
    const res = await request(app).get('/api/kpis/download/excel');
    expect(res.statusCode).toEqual(200);
    expect(res.headers['content-type']).toContain('application/vnd.ms-excel');
  });
});