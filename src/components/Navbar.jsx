import React from 'react';
import { Link, useLocation } from 'react-router-dom';

const Navbar = ({ user, onLogout }) => {
  const location = useLocation();
  
  const isActive = (path) => {
    return location.pathname === path ? 'bg-blue-700' : '';
  };

  return (
    <nav className="bg-blue-600 text-white shadow-md">
      <div className="container mx-auto px-4">
        <div className="flex justify-between items-center py-3">
          <div className="text-xl font-bold">Employee Management</div>
          <div className="flex items-center space-x-4">
            <Link to="/" className={`px-3 py-2 rounded hover:bg-blue-700 ${isActive('/')}`}>
              Dashboard
            </Link>
            <Link to="/employees" className={`px-3 py-2 rounded hover:bg-blue-700 ${isActive('/employees')}`}>
              Employees
            </Link>
            <Link to="/add-employee" className={`px-3 py-2 rounded hover:bg-blue-700 ${isActive('/add-employee')}`}>
              Add Employee
            </Link>
            <div className="flex items-center ml-6">
              <span className="mr-3">Welcome, {user?.name || 'User'}</span>
              <button 
                onClick={onLogout}
                className="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded"
              >
                Logout
              </button>
            </div>
          </div>
        </div>
      </div>
    </nav>
  );
};

export default Navbar;