const request = require('supertest');
const path = require('path');
const app = require('../api/index'); // Assuming this is the entry point for the API

describe('API Endpoints', () => {
  // KPI Tests
  it('should retrieve all KPIs', async () => {
    const res = await request(app).get('/api/kpis');
    expect(res.statusCode).toEqual(200);
    expect(res.body).toHaveProperty('data');
  });

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

  // File Upload Test
  it('should upload a CSV file and return JSON', async () => {
    const csvPath = path.join(__dirname, 'test_kpis.csv');
    const fs = require('fs');

    // Create a sample CSV file for testing
    fs.writeFileSync(csvPath, 'name,value\nKPI1,123\nKPI2,456');

    const res = await request(app)
      .post('/api/kpis/upload')
      .attach('file', csvPath);

    expect(res.statusCode).toEqual(200);
    expect(res.body).toHaveProperty('json');
    expect(Array.isArray(res.body.json)).toBe(true);
    expect(res.body.json[0]).toHaveProperty('name');
    expect(res.body.json[0]).toHaveProperty('value');

    // Clean up test file
    fs.unlinkSync(csvPath);
  });

  // Additional Tests for Other Endpoints
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
});