import React from "react";

export default function StatusBadge({ status }) {
  const colors = {
    available: "#16a34a",      
    unavailable: "#924a4aff",    
    borrowed: "#6b7280"            
  };

  const key = status.replace(" ", "_").toLowerCase();
  const bg = colors[key] || "#6b7280";

  return (
    <span
      style={{
        backgroundColor: bg,
        color: "#fff",
        padding: "4px 8px",
        borderRadius: "4px",
        fontSize: "12px",
        textTransform: "capitalize"
      }}
    >
      {status}
    </span>
  );
}
