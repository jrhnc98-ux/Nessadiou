-- Migration de l'ancien système (les lignes de "commandes" étaient le panier)
-- vers un panier séparé et de vraies commandes contenant plusieurs articles.
USE nessadiou;

CREATE TABLE IF NOT EXISTS panier (
  id INT AUTO_INCREMENT PRIMARY KEY,
  client_id INT NOT NULL,
  produit_id INT NOT NULL,
  quantite INT NOT NULL DEFAULT 1,
  date_ajout TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_panier_client_produit (client_id, produit_id),
  FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
  FOREIGN KEY (produit_id) REFERENCES produits(id) ON DELETE CASCADE
);

-- Sur une ancienne installation, exécuter ces deux instructions avant la suite :
-- INSERT INTO panier (client_id, produit_id, quantite, date_ajout)
-- SELECT client_id, produit_id, quantite, date_commande FROM commandes;
-- DROP TABLE commandes;

CREATE TABLE IF NOT EXISTS commandes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  client_id INT NOT NULL,
  statut ENUM('En cours', 'En préparation', 'En livraison', 'Livré', 'Annulée') NOT NULL DEFAULT 'En cours',
  date_commande TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  total DECIMAL(12, 2) NOT NULL DEFAULT 0,
  raison_annulation TEXT NULL,
  date_annulation DATETIME NULL,
  FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS commande_produit (
  id INT AUTO_INCREMENT PRIMARY KEY,
  commande_id INT NOT NULL,
  produit_id INT NULL,
  nom_produit VARCHAR(100) NOT NULL,
  prix_unitaire DECIMAL(10, 2) NOT NULL,
  quantite INT NOT NULL DEFAULT 1,
  image TEXT,
  FOREIGN KEY (commande_id) REFERENCES commandes(id) ON DELETE CASCADE,
  FOREIGN KEY (produit_id) REFERENCES produits(id) ON DELETE SET NULL
);
