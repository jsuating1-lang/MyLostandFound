import { useState } from "react";

export default function SearchByImage() {
  const [matches, setMatches] = useState([]);

  async function handleSearch(e) {
    e.preventDefault();
    const form = new FormData(e.target);
    const res = await fetch("http://localhost:8000/search/image", {
      method: "POST",
      body: form,
    });
    const data = await res.json();
    setMatches(data.matches);
  }

  return (
    <div>
      <h2>Search Found Items by Image</h2>
      <form onSubmit={handleSearch}>
        <input type="file" name="image" accept="image/*" required />
        <button type="submit">Find Similar Items</button>
      </form>
      <ul>
        {matches.map((m) => (
          <li key={m.item_id}>
            Item #{m.item_id} — {(m.similarity_score * 100).toFixed(1)}% match
          </li>
        ))}
      </ul>
    </div>
  );
}