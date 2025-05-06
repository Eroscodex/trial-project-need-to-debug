import React, { useState, useEffect } from 'react';
import axios from 'axios';

const Dashboard = ({ user }) => {
  const [stats, setStats] = useState({
    totalEmployees: 0,
    recentHires: 0,
    departmentCounts: [],
    activityLogs: []
  });
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState('');

  useEffect(() => {
    const fetchDashboardData = async () => {
      setIsLoading(true);
      try {
        // Get employee stats
        const employeesRes = await axios.get('http://localhost/FINAL%20PROJECT/EMS_BACKEND/api/employees.php?stats=true', {
          headers: {
            Authorization: `Bearer ${localStorage.getItem('token')}`,
          }
        });
        
        // Get recent activity logs
        const logsRes = await axios.get('http://localhost/FINAL%20PROJECT/EMS_BACKEND/api/activity_log.php?limit=5', {
          headers: {
            Authorization: `Bearer ${localStorage.getItem('token')}`,
          }
        });
        
        setStats({
          totalEmployees: employeesRes.data.totalCount || 0,
          recentHires: employeesRes.data.recentHires || 0,
          departmentCounts: employeesRes.data.departmentCounts || [],
          activityLogs: logsRes.data.logs || []
        });
      } catch (error) {
        setError('Failed to load dashboard data');
        console.error(error);
      } finally {
        setIsLoading(false);
      }
    };

    fetchDashboardData();
  }, []);

  if (isLoading) {
    return (
      <div className="flex justify-center items-center h-64">
        <div className="text-gray-500">Loading dashboard data...</div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
        {error}
      </div>
    );
  }

  return (
    <div>
      <h1 className="text-2xl font-bold mb-6">Dashboard</h1>
      
      <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div className="bg-white p-6 rounded-lg shadow-md">
          <h2 className="text-lg font-semibold text-gray-700 mb-2">Total Employees</h2>
          <p className="text-3xl font-bold text-blue-600">{stats.totalEmployees}</p>
        </div>
        
        <div className="bg-white p-6 rounded-lg shadow-md">
          <h2 className="text-lg font-semibold text-gray-700 mb-2">Recent Hires (30 days)</h2>
          <p className="text-3xl font-bold text-green-600">{stats.recentHires}</p>
        </div>
        
        <div className="bg-white p-6 rounded-lg shadow-md">
          <h2 className="text-lg font-semibold text-gray-700 mb-2">User Role</h2>
          <p className="text-3xl font-bold text-purple-600">{user?.role || 'User'}</p>
        </div>
      </div>
      
      <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div className="bg-white p-6 rounded-lg shadow-md">
          <h2 className="text-lg font-semibold text-gray-700 mb-4">Department Distribution</h2>
          {stats.departmentCounts.length > 0 ? (
            <div className="space-y-3">
              {stats.departmentCounts.map((dept) => (
                <div key={dept.department} className="flex justify-between items-center">
                  <span className="text-gray-700">{dept.department}</span>
                  <span className="font-semibold">{dept.count}</span>
                </div>
              ))}
            </div>
          ) : (
            <p className="text-gray-500">No department data available</p>
          )}
        </div>
        
        <div className="bg-white p-6 rounded-lg shadow-md">
          <h2 className="text-lg font-semibold text-gray-700 mb-4">Recent Activity</h2>
          {stats.activityLogs.length > 0 ? (
            <div className="space-y-3">
              {stats.activityLogs.map((log) => (
                <div key={log.id} className="border-b pb-2">
                  <p className="text-sm">
                    <span className="font-medium">{log.user_name}</span> {log.action} 
                    {log.employee_name && <span> for {log.employee_name}</span>}
                  </p>
                  <p className="text-xs text-gray-500">{new Date(log.timestamp).toLocaleString()}</p>
                </div>
              ))}
            </div>
          ) : (
            <p className="text-gray-500">No recent activity</p>
          )}
        </div>
      </div>
    </div>
  );
};

export default Dashboard;