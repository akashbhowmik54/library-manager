import React, { useEffect, useState } from "react";
import { apiGet, apiDelete } from "../api";
import Loader from "./Loader";
import StatusBadge from "./StatusBadge";

export default function BookList({ onAdd, onEdit }) {
  const [books, setBooks] = useState([]);
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState("");

  const loadBooks = async () => {
    setLoading(true);
    const data = await apiGet("/books");
    setBooks(data);
    setLoading(false);
  };

  useEffect(() => {
    loadBooks();
  }, []);

  const handleDelete = async (id) => {
    if (!confirm("Are you sure you want to delete this book?")) return;

    await apiDelete(`/books/${id}`);
    loadBooks();
  };

  const filteredBooks = books.filter((book) =>
    book.title.toLowerCase().includes(search.toLowerCase()) ||
    book.author.toLowerCase().includes(search.toLowerCase())
  );

  if (loading) return <Loader />;

  return (
    <div>
      <h2>Book List</h2>

      <button className="btn" onClick={onAdd} style={{ marginBottom: "10px" }}>
        + Add New Book
      </button>

      <div style={{ marginBottom: "15px" }}>
        <input
          type="text"
          placeholder="Search by title or author..."
          value={search}
          onChange={(e) => setSearch(e.target.value)}
          style={{
            padding: "8px",
            width: "250px",
            border: "1px solid #ccc",
            borderRadius: "4px"
          }}
        />
      </div>

      <table className="book-table">
        <thead>
          <tr>
            <th>Title</th>
            <th>Author</th>
            <th>Year</th>
            <th>Status</th>
            <th width="140">Actions</th>
          </tr>
        </thead>

        <tbody>
          {filteredBooks.length === 0 && (
            <tr><td colSpan="5">No books found.</td></tr>
          )}

          {filteredBooks.map((b) => (
            <tr key={b.id}>
              <td>{b.title}</td>
              <td>{b.author}</td>
              <td>{b.publication_year}</td>
              <td>
                <StatusBadge status={b.status} />
              </td>
              <td>
                <button className="btn-sm" onClick={() => onEdit(b.id)}>Edit</button>
                <button
                  className="btn-sm danger"
                  onClick={() => handleDelete(b.id)}
                >Delete</button>
              </td>
            </tr>
          ))}
        </tbody>

      </table>
    </div>
  );
}
