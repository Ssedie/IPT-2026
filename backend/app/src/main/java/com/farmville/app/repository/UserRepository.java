package com.farmville.app.repository;

import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.stereotype.Repository;

import com.farmville.app.entity.Users;

@Repository
public interface UserRepository extends JpaRepository<Users, Long> {
    
}
