import React, { useState } from "react";
import BookList from "./components/BookList";
import BookForm from "./components/BookForm";

export default function App() {
  const [mode, setMode] = useState("list");
  const [editBookId, setEditBookId] = useState(null);

  const handleEdit = (id) => {
    setEditBookId(id);
    setMode("edit");
  };

  const handleAdd = () => setMode("add");
  const handleBack = () => setMode("list");

  return (
    <div className="library-container">
      {mode === "list" && (
        <BookList onAdd={handleAdd} onEdit={handleEdit} />
      )}

      {mode === "add" && (
        <BookForm type="add" onBack={handleBack} />
      )}

      {mode === "edit" && (
        <BookForm type="edit" bookId={editBookId} onBack={handleBack} />
      )}
    </div>
  );
}
