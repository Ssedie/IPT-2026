package com.farmville.app.repository;

import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.stereotype.Repository;
import com.farmville.app.entity.Products;


@Repository
public interface ProductRepository extends JpaRepository<Products, Long> {
    
}
