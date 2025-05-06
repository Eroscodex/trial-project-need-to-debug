import React from 'react';
import { Routes, Route, Navigate } from 'react-router-dom';
import Dashboard from './pages/Dashboard';
import EmployeeList from './pages/EmployeeList';
import AddEmployee from './pages/AddEmployee';
import EditEmployee from './pages/EditEmployee';
import Login from './pages/Login';
import NoPage from './pages/NoPage';

// Protected route component
const ProtectedRoute = ({ children, isAuthenticated }) => {
  if (!isAuthenticated) {
    return <Navigate to="/login" />;
  }
  return children;
};

const AppRoutes = ({ isAuthenticated, user, setIsAuthenticated, setUser }) => {
  return (
    <Routes>
      <Route path="/login" element={<Login setIsAuthenticated={setIsAuthenticated} setUser={setUser} />} />
      
      <Route path="/" element={
        <ProtectedRoute isAuthenticated={isAuthenticated}>
          <Dashboard user={user} />
        </ProtectedRoute>
      } />
      
      <Route path="/employees" element={
        <ProtectedRoute isAuthenticated={isAuthenticated}>
          <EmployeeList />
        </ProtectedRoute>
      } />
      
      <Route path="/add-employee" element={
        <ProtectedRoute isAuthenticated={isAuthenticated}>
          <AddEmployee user={user} />
        </ProtectedRoute>
      } />
      
      <Route path="/edit-employee/:id" element={
        <ProtectedRoute isAuthenticated={isAuthenticated}>
          <EditEmployee user={user} />
        </ProtectedRoute>
      } />
      
      <Route path="*" element={<NoPage />} />
    </Routes>
  );
};

export default AppRoutes;

