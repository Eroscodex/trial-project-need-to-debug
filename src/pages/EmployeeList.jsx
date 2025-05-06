import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import axios from 'axios';
import SearchBar from '../components/SearchBar';

const EmployeeList = () => {
  const [employees, setEmployees] = useState([]);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState('');
  const [currentPage, setCurrentPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);
  const [searchQuery, setSearchQuery] = useState('');

  const fetchEmployees = async (page = 1, search = '') => {
    setIsLoading(true);
    try {
      const response = await axios.get('http://localhost/FINAL%20PROJECT/EMS_BACKEND/api/employees.php', {
        params: { page, limit: 10, search },
        headers: {
          Authorization: `Bearer ${localStorage.getItem('token')}`,
        }
      });
      
      setEmployees(response.data.employees || []);
      setTotalPages(response.data.totalPages || 1);
      setError('');
    } catch (error) {
      setError('Failed to load employees');
      console.error(error);
    } finally {
      setIsLoading(false);
    }
  };

  useEffect(() => {
    fetchEmployees(currentPage, searchQuery);
  }, [currentPage, searchQuery]);

  const handleSearch = (query) => {
    setSearchQuery(query);
    setCurrentPage(1); // Reset to first page when searching
  };

  const handleDelete = async (id) => {
    if (window.confirm('Are you sure you want to delete this employee?')) {
      try {
        await axios.delete(`http://localhost/FINAL%20PROJECT/EMS_BACKEND/api/delete_employee.php?id=${id}`, {
          headers: {
            Authorization: `Bearer ${localStorage.getItem('token')}`,
          }
        });
        
        // Refresh the list
        fetchEmployees(currentPage, searchQuery);
      } catch (error) {
        alert('Failed to delete employee');
        console.error(error);
      }
    }
  };

  return (
    <div>
      <div className="flex justify-between items-center mb-6">
        <h1 className="text-2xl font-bold">Employee List</h1>
        <Link 
          to="/add-employee" 
          className="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700"
        >
          Add New Employee
        </Link>
      </div>
      
      <SearchBar onSearch={handleSearch} />
      
      {error && (
        <div className="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
          {error}
        </div>
      )}
      
      {isLoading ? (
        <div className="flex justify-center items-center h-64">
          <div className="text-gray-500">Loading employees...</div>
        </div>
      ) : (
        <>
          {employees.length > 0 ? (
            <div className="overflow-x-auto">
              <table className="min-w-full bg-white border border-gray-200 rounded-lg shadow">
                <thead className="bg-gray-100">
                  <tr>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">ID</th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Name</th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Email</th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Department</th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Position</th>
                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Hire Date</th>
                    <th className="px-6 py-3 text-center text-xs font-medium text-gray-600 uppercase tracking-wider">Actions</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-gray-200">
                  {employees.map((employee) => (
                    <tr key={employee.id} className="hover:bg-gray-50">
                      <td className="px-6 py-4 whitespace-nowrap">{employee.id}</td>
                      <td className="px-6 py-4 whitespace-nowrap">
                        {employee.profile_image ? (
                          <div className="flex items-center">
                            <img 
                              src={`http://localhost/FINAL%20PROJECT/EMS_BACKEND/uploads/${employee.profile_image}`} 
                              alt={employee.name} 
                              className="h-8 w-8 rounded-full mr-2"
                            />
                            {employee.name}
                          </div>
                        ) : (
                          employee.name
                        )}
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap">{employee.email}</td>
                      <td className="px-6 py-4 whitespace-nowrap">{employee.department}</td>
                      <td className="px-6 py-4 whitespace-nowrap">{employee.position}</td>
                      <td className="px-6 py-4 whitespace-nowrap">{employee.hire_date}</td>
                      <td className="px-6 py-4 whitespace-nowrap text-center">
                        <Link 
                          to={`/edit-employee/${employee.id}`}
                          className="text-blue-600 hover:text-blue-800 mr-4"
                        >
                          Edit
                        </Link>
                        <button 
                          onClick={() => handleDelete(employee.id)}
                          className="text-red-600 hover:text-red-800"
                        >
                          Delete
                        </button>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          ) : (
            <div className="bg-gray-50 border border-gray-200 text-gray-700 px-4 py-8 rounded text-center">
              No employees found. {searchQuery ? 'Try a different search term.' : ''}
            </div>
          )}
          
          {/* Pagination */}
          {totalPages > 1 && (
            <div className="flex justify-center mt-6">
              <nav className="flex items-center">
                <button
                  onClick={() => setCurrentPage(Math.max(1, currentPage - 1))}
                  disabled={currentPage === 1}
                  className="px-3 py-1 border rounded mr-2 disabled:opacity-50"
                >
                  Previous
                </button>
                <div className="text-gray-700">
                  Page {currentPage} of {totalPages}
                </div>
                <button
                  onClick={() => setCurrentPage(Math.min(totalPages, currentPage + 1))}
                  disabled={currentPage === totalPages}
                  className="px-3 py-1 border rounded ml-2 disabled:opacity-50"
                >
                  Next
                </button>
              </nav>
            </div>
          )}
        </>
      )}
    </div>
  );
};

export default EmployeeList;