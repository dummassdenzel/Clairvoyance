import type { PageLoad } from './$types';

export const load: PageLoad = async () => {
  // Mock data for users
  const users = [
    { id: 1, name: 'John Doe', email: 'john.doe@example.com', role: 'editor', status: 'active' },
    { id: 2, name: 'Jane Smith', email: 'jane.smith@example.com', role: 'viewer', status: 'active' },
    { id: 3, name: 'Peter Jones', email: 'peter.jones@example.com', role: 'viewer', status: 'inactive' },
    { id: 4, name: 'Admin User', email: 'admin@example.com', role: 'admin', status: 'active' },
    { id: 5, name: 'Sam Wilson', email: 'sam.wilson@example.com', role: 'editor', status: 'active' },
  ];

  return {
    users
  };
};
