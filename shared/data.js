/* Auction Arena · sample live-auction payload.
   Every template reads ONLY from window.AUCTION, so wiring to the real
   backend means replacing this object (or calling AA.update(patch)). */
window.AUCTION = {
  tournament: { name: "Greenfield Cricket League", season: "Season 6 · 2026", logo: "../assets/tournament-logo.svg" },
  sponsor: { label: "Title Sponsor", name: "Jay Innovations", logo: "../assets/sponsor-logo.svg" },
  brand: { name: "Auction Arena", logo: "../assets/auction-arena-logo.svg" },
  player: {
    name: "Jash Vijay Doshi",
    first: "Jash Vijay",
    last: "Doshi",
    number: "17",
    photo: "../assets/player.svg",
    grade: "Icon",
    age: 25,
    role: "Batsman",
    style: "Right-hand bat · Right-arm off-break",
    basePrice: "1.00L",
    stats: [
      { label: "Matches", value: "86" },
      { label: "Runs", value: "2,947" },
      { label: "Average", value: "41.5" },
      { label: "Strike Rate", value: "148.2" },
      { label: "50s / 100s", value: "21 / 4" },
      { label: "High Score", value: "132*" },
      { label: "Wickets", value: "18" },
      { label: "Economy", value: "7.9" }
    ]
  },
  status: { available: 100, sold: 40, unsold: 60 },
  bid: { amount: "1.00", unit: "CR", raw: 10000000, increment: 500000 },
  team: { name: "Meena Sports", short: "MS", logo: "../assets/team-logo.svg", maxBid: "54.3L", purse: "8.2CR", slots: "7 / 15" }
};
