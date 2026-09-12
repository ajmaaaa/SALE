import unittest


class TestBST(unittest.TestCase):

    def setUp(self):
        self.tree = BinaryTree()

    def test_01_empty_tree_init(self):
        """Pohon kosong: insert satu node harus mengembalikan Node baru sebagai root."""
        root = self.tree.insert(None, 10)
        self.assertIsNotNone(root, "insert(None, 10) harus mengembalikan Node, bukan None")
        self.assertEqual(root.value, 10, "Nilai root harus 10")

    def test_02_branch_left_recur(self):
        """Key lebih kecil dari root harus masuk ke cabang kiri."""
        root = self.tree.insert(None, 10)
        root = self.tree.insert(root, 5)
        self.assertIsNotNone(root.left, "root.left harus berisi Node setelah insert nilai 5 (lebih kecil dari 10)")
        self.assertEqual(root.left.value, 5, "root.left.value harus 5")

    def test_03_branch_right_recur(self):
        """Key lebih besar dari root harus masuk ke cabang kanan."""
        root = self.tree.insert(None, 10)
        root = self.tree.insert(root, 15)
        self.assertIsNotNone(root.right, "root.right harus berisi Node setelah insert nilai 15 (lebih besar dari 10)")
        self.assertEqual(root.right.value, 15, "root.right.value harus 15")

    def test_04_root_reference_integrity(self):
        """Root asli tidak boleh berubah setelah beberapa insert."""
        root = self.tree.insert(None, 10)
        root = self.tree.insert(root, 5)
        root = self.tree.insert(root, 15)
        root = self.tree.insert(root, 3)
        self.assertEqual(root.value, 10, "Root harus tetap 10 setelah beberapa insert")
        self.assertEqual(root.left.value, 5)
        self.assertEqual(root.right.value, 15)
        self.assertEqual(root.left.left.value, 3)


if __name__ == '__main__':
    unittest.main(verbosity=2)
