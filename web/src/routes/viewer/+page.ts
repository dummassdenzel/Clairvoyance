import type { PageLoad } from './$types';

export const load: PageLoad = async () => {
  // Mock data for dashboards
  const dashboards = [
    { id: 1, name: 'Monthly Sales Performance', description: 'Tracks key sales metrics for the current month.' },
    { id: 2, name: 'Website Analytics Overview', description: 'Monitors website traffic, user engagement, and conversion rates.' },
    { id: 3, name: 'Customer Support KPIs', description: 'Key performance indicators for the customer support team.' },
    { id: 4, name: 'Marketing Campaign ROI', description: 'Analysis of return on investment for recent marketing campaigns.' },
  ];

  return {
    dashboards
  };
};
