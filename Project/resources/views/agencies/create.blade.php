<h2>Create Agency</h2>

<form method="POST" action="/agencies">
    @csrf

    <input type="text" name="name" placeholder="Agency Name" required>
    <br><br>

    <input type="text" name="phone" placeholder="Phone">
    <br><br>

    <input type="text" name="address" placeholder="Address">
    <br><br>

    <button type="submit">Submit Agency</button>
</form>