import React, { useState, useEffect } from "react";
import { apiGet, apiPost, apiPut } from "../api";
import Loader from "./Loader";

export default function BookForm({ type, onBack, bookId }) {
  const [form, setForm] = useState({
    title: "",
    author: "",
    description: "",
    publication_year: "",
    status: "available"
  });

  const [loading, setLoading] = useState(type === "edit");

  useEffect(() => {
    if (type === "edit") {
      loadBook();
    }
  }, []);

  const loadBook = async () => {
    const data = await apiGet(`/books/${bookId}`);
    setForm(data);
    setLoading(false);
  };

  const handleChange = (e) => {
    setForm({ ...form, [e.target.name]: e.target.value });
  };

  const handleSubmit = async () => {
    if (!form.title) {
      alert("Title is required.");
      return;
    }

    if (type === "add") {
      await apiPost("/books", form);
    } else {
      await apiPut(`/books/${bookId}`, form);
    }

    onBack(); // go back to list
  };

  if (loading) return <Loader />;

  return (
    <div>
      <h2>{type === "add" ? "Add New Book" : "Edit Book"}</h2>

      <div className="form-group">
        <label>Title *</label>
        <input name="title" value={form.title} onChange={handleChange} />
      </div>

      <div className="form-group">
        <label>Author</label>
        <input name="author" value={form.author} onChange={handleChange} />
      </div>

      <div className="form-group">
        <label>Description</label>
        <textarea name="description" value={form.description} onChange={handleChange}></textarea>
      </div>

      <div className="form-group">
        <label>Publication Year</label>
        <input type="number" name="publication_year" value={form.publication_year} onChange={handleChange} />
      </div>

      <div className="form-group">
        <label>Status</label>
        <select name="status" value={form.status} onChange={handleChange}>
          <option value="available">Available</option>
          <option value="borrowed">Borrowed</option>
          <option value="unavailable">Unavailable</option>
        </select>
      </div>

      <button className="btn" onClick={handleSubmit}>
        {type === "add" ? "Add Book" : "Save Changes"}
      </button>

      <button className="btn secondary" onClick={onBack}>Back</button>
    </div>
  );
}
