import type { PageLoad } from './$types';
import { error } from '@sveltejs/kit';

export const load: PageLoad = async ({ params }) => {
  // In a real app, you'd fetch this from an API
  const dashboards = [
    { id: '1', name: 'Monthly Sales Performance', description: 'Tracks key sales metrics for the current month.' },
    { id: '2', name: 'Website Analytics Overview', description: 'Monitors website traffic, user engagement, and conversion rates.' },
    { id: '3', name: 'Customer Support KPIs', description: 'Key performance indicators for the customer support team.' },
    { id: '4', name: 'Marketing Campaign ROI', description: 'Analysis of return on investment for recent marketing campaigns.' },
  ];

  const dashboard = dashboards.find(d => d.id === params.id);

  if (!dashboard) {
    throw error(404, 'Dashboard not found');
  }

  return {
    dashboard
  };
};
